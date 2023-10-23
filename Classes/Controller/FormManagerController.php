<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Form\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Form\Exception as FormException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * The form manager controller
 *
 * Scope: backend
 * @internal
 */
class FormManagerController extends AbstractBackendController
{
    protected const JS_MODULE_NAMES = ['app', 'viewModel'];

    protected int $limit = 20;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        // @todo: unit tests set next one, so no readonly. refactor them to functionals?!
        protected DatabaseService $databaseService,
    ) {}

    /**
     * Display the Form Manager. The main showing available forms.
     */
    protected function indexAction(int $page = 1, string $searchTerm = ''): ResponseInterface
    {
        $hasForms = $this->formPersistenceManager->hasForms();
        $forms = $hasForms ? $this->getAvailableFormDefinitions(trim($searchTerm)) : [];
        $arrayPaginator = new ArrayPaginator($forms, $page, $this->limit);
        $pagination = new SimplePagination($arrayPaginator);

        $moduleTemplate = $this->initializeModuleTemplate($this->request, $page, $searchTerm);
        $moduleTemplate->assignMultiple([
            'paginator' => $arrayPaginator,
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'hasForms' => $hasForms,
            'stylesheets' => $this->resolveResourcePaths($this->formSettings['formManager']['stylesheets']),
            'formManagerAppInitialData' => json_encode($this->getFormManagerAppInitialData()),
        ]);
        if (!empty($this->formSettings['formManager']['javaScriptTranslationFile'])) {
            $this->pageRenderer->addInlineLanguageLabelFile($this->formSettings['formManager']['javaScriptTranslationFile']);
        }

        $javaScriptModules = array_map(
            static fn(string $name) => JavaScriptModuleInstruction::create($name),
            array_filter(
                $this->formSettings['formManager']['dynamicJavaScriptModules'] ?? [],
                fn(string $name) => in_array($name, self::JS_MODULE_NAMES, true),
                ARRAY_FILTER_USE_KEY
            )
        );
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/form/backend/helper.js', 'Helper')
                ->invoke('dispatchFormManager', $javaScriptModules, $this->getFormManagerAppInitialData())
        );
        array_map($this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(...), $javaScriptModules);
        $moduleTemplate->setModuleClass($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
        $moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/locallang_module.xlf:mlang_tabs_tab')
        );
        return $moduleTemplate->renderResponse('Backend/FormManager/Index');
    }

    /**
     * Initialize the "create" action.
     * This action uses the Fluid JsonView::class as view.
     */
    protected function initializeCreateAction(): void
    {
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Creates a new Form and redirects to the Form Editor
     *
     * @throws FormException
     * @throws PersistenceManagerException
     */
    protected function createAction(string $formName, string $templatePath, string $prototypeName, string $savePath): ResponseInterface
    {
        if (!$this->formPersistenceManager->isAllowedPersistencePath($savePath)) {
            throw new PersistenceManagerException(sprintf('Save to path "%s" is not allowed', $savePath), 1614500657);
        }

        if (!$this->isValidTemplatePath($prototypeName, $templatePath)) {
            throw new FormException(sprintf('The template path "%s" is not allowed', $templatePath), 1329233410);
        }
        if (empty($formName)) {
            throw new FormException('No form name', 1472312204);
        }

        $templatePath = GeneralUtility::getFileAbsFileName($templatePath);
        $form = Yaml::parse((string)file_get_contents($templatePath));
        $form['label'] = $formName;
        $form['identifier'] = $this->formPersistenceManager->getUniqueIdentifier($this->convertFormNameToIdentifier($formName));
        $form['prototypeName'] = $prototypeName;

        $formPersistenceIdentifier = $this->formPersistenceManager->getUniquePersistenceIdentifier($form['identifier'], $savePath);

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormCreate'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeFormCreate')) {
                $form = $hookObj->beforeFormCreate(
                    $formPersistenceIdentifier,
                    $form
                );
            }
        }

        $response = [
            'status' => 'success',
            'url' => $this->uriBuilder->uriFor('index', ['formPersistenceIdentifier' => $formPersistenceIdentifier], 'FormEditor'),
        ];

        try {
            $this->formPersistenceManager->save($formPersistenceIdentifier, $form);
        } catch (PersistenceManagerException $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }

        $this->view->assign('response', $response);
        // createAction uses the Extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        $this->view->setVariablesToRender([
            'response',
        ]);

        return $this->jsonResponse();
    }

    /**
     * Initialize the duplicate action.
     * This action uses the Fluid JsonView::class as view.
     */
    protected function initializeDuplicateAction(): void
    {
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Duplicates a given formDefinition and redirects to the Form Editor
     *
     * @throws PersistenceManagerException
     */
    protected function duplicateAction(string $formName, string $formPersistenceIdentifier, string $savePath): ResponseInterface
    {
        if (!$this->formPersistenceManager->isAllowedPersistencePath($savePath)) {
            throw new PersistenceManagerException(sprintf('Save to path "%s" is not allowed', $savePath), 1614500658);
        }
        if (!$this->formPersistenceManager->isAllowedPersistencePath($formPersistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('Read of "%s" is not allowed', $formPersistenceIdentifier), 1614500659);
        }

        $formToDuplicate = $this->formPersistenceManager->load($formPersistenceIdentifier);
        $formToDuplicate['label'] = $formName;
        $formToDuplicate['identifier'] = $this->formPersistenceManager->getUniqueIdentifier($this->convertFormNameToIdentifier($formName));

        $formPersistenceIdentifier = $this->formPersistenceManager->getUniquePersistenceIdentifier($formToDuplicate['identifier'], $savePath);

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDuplicate'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeFormDuplicate')) {
                $formToDuplicate = $hookObj->beforeFormDuplicate(
                    $formPersistenceIdentifier,
                    $formToDuplicate
                );
            }
        }

        $response = [
            'status' => 'success',
            'url' => $this->uriBuilder->uriFor('index', ['formPersistenceIdentifier' => $formPersistenceIdentifier], 'FormEditor'),
        ];

        try {
            $this->formPersistenceManager->save($formPersistenceIdentifier, $formToDuplicate);
        } catch (PersistenceManagerException $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }

        $this->view->assign('response', $response);
        // createAction uses the Extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        $this->view->setVariablesToRender([
            'response',
        ]);

        return $this->jsonResponse();
    }

    /**
     * Initialize the references action.
     * This action uses the Fluid JsonView::class as view.
     */
    protected function initializeReferencesAction(): void
    {
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Show references to this persistence identifier
     *
     * @throws PersistenceManagerException
     */
    protected function referencesAction(string $formPersistenceIdentifier): ResponseInterface
    {
        if (!$this->formPersistenceManager->isAllowedPersistencePath($formPersistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('Read from "%s" is not allowed', $formPersistenceIdentifier), 1614500660);
        }

        $this->view->assign('references', $this->getProcessedReferencesRows($formPersistenceIdentifier));
        $this->view->assign('formPersistenceIdentifier', $formPersistenceIdentifier);
        // referencesAction uses the extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        $this->view->setVariablesToRender([
            'references',
            'formPersistenceIdentifier',
        ]);

        return $this->jsonResponse();
    }

    /**
     * Delete a formDefinition identified by the $formPersistenceIdentifier.
     *
     * @throws PersistenceManagerException
     */
    protected function deleteAction(string $formPersistenceIdentifier): ResponseInterface
    {
        if (!$this->formPersistenceManager->isAllowedPersistencePath($formPersistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('Delete "%s" is not allowed', $formPersistenceIdentifier), 1614500661);
        }

        if (empty($this->databaseService->getReferencesByPersistenceIdentifier($formPersistenceIdentifier))) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete'] ?? [] as $className) {
                $hookObj = GeneralUtility::makeInstance($className);
                if (method_exists($hookObj, 'beforeFormDelete')) {
                    $hookObj->beforeFormDelete(
                        $formPersistenceIdentifier
                    );
                }
            }

            $this->formPersistenceManager->delete($formPersistenceIdentifier);
        } else {
            $controllerConfiguration = GeneralUtility::makeInstance(TranslationService::class)->translateValuesRecursive(
                $this->formSettings['formManager']['controller'],
                $this->formSettings['formManager']['translationFiles'] ?? []
            );

            $this->addFlashMessage(
                sprintf($controllerConfiguration['deleteAction']['errorMessage'], $formPersistenceIdentifier),
                $controllerConfiguration['deleteAction']['errorTitle'],
                ContextualFeedbackSeverity::ERROR,
                true
            );
        }
        return $this->redirect('index');
    }

    /**
     * Return a list of all accessible file mountpoints.
     *
     * Only registered mount points from
     * persistenceManager.allowedFileMounts
     * are listed. This list will be reduced by the configured
     * mount points for the current backend user.
     */
    protected function getAccessibleFormStorageFolders(): array
    {
        $preparedAccessibleFormStorageFolders = [];
        foreach ($this->formPersistenceManager->getAccessibleFormStorageFolders() as $identifier => $folder) {
            $preparedAccessibleFormStorageFolders[] = [
                'label' => $folder->getStorage()->isPublic() ? $folder->getPublicUrl() : $identifier,
                'value' => $identifier,
            ];
        }

        if ($this->formSettings['persistenceManager']['allowSaveToExtensionPaths']) {
            foreach ($this->formPersistenceManager->getAccessibleExtensionFolders() as $relativePath => $fullPath) {
                $preparedAccessibleFormStorageFolders[] = [
                    'label' => $relativePath,
                    'value' => $relativePath,
                ];
            }
        }

        return $preparedAccessibleFormStorageFolders;
    }

    /**
     * Returns the json encoded data which is used by the form editor
     * JavaScript app.
     */
    protected function getFormManagerAppInitialData(): array
    {
        $formManagerAppInitialData = [
            'selectablePrototypesConfiguration' => $this->formSettings['formManager']['selectablePrototypesConfiguration'],
            'accessibleFormStorageFolders' => $this->getAccessibleFormStorageFolders(),
            'endpoints' => [
                'create' => $this->uriBuilder->uriFor('create'),
                'duplicate' => $this->uriBuilder->uriFor('duplicate'),
                'delete' => $this->uriBuilder->uriFor('delete'),
                'references' => $this->uriBuilder->uriFor('references'),
            ],
        ];

        $formManagerAppInitialData = ArrayUtility::reIndexNumericArrayKeysRecursive($formManagerAppInitialData);
        $formManagerAppInitialData = GeneralUtility::makeInstance(TranslationService::class)->translateValuesRecursive(
            $formManagerAppInitialData,
            $this->formSettings['formManager']['translationFiles'] ?? []
        );
        return $formManagerAppInitialData;
    }

    /**
     * List all formDefinitions which can be loaded through t form persistence
     * manager. Enrich this data by a reference counter.
     */
    protected function getAvailableFormDefinitions(string $searchTerm = ''): array
    {
        $allReferencesForFileUid = $this->databaseService->getAllReferencesForFileUid();
        $allReferencesForPersistenceIdentifier = $this->databaseService->getAllReferencesForPersistenceIdentifier();

        $availableFormDefinitions = [];
        foreach ($this->formPersistenceManager->listForms() as $formDefinition) {
            $referenceCount  = 0;
            if (
                isset($formDefinition['fileUid'])
                && array_key_exists($formDefinition['fileUid'], $allReferencesForFileUid)
            ) {
                $referenceCount = $allReferencesForFileUid[$formDefinition['fileUid']];
            } elseif (array_key_exists($formDefinition['persistenceIdentifier'], $allReferencesForPersistenceIdentifier)) {
                $referenceCount = $allReferencesForPersistenceIdentifier[$formDefinition['persistenceIdentifier']];
            }

            $formDefinition['referenceCount'] = $referenceCount;
            if ($searchTerm === ''
                || $this->valueContainsSearchTerm($formDefinition['name'], $searchTerm)
                || $this->valueContainsSearchTerm($formDefinition['persistenceIdentifier'], $searchTerm)
            ) {
                $availableFormDefinitions[] = $formDefinition;
            }
        }

        return $availableFormDefinitions;
    }

    protected function valueContainsSearchTerm(string $value, string $searchTerm): bool
    {
        return str_contains(strtolower($value), strtolower($searchTerm));
    }

    /**
     * Returns an array with information about the references for a
     * formDefinition identified by $persistenceIdentifier.
     *
     * @throws \InvalidArgumentException
     */
    protected function getProcessedReferencesRows(string $persistenceIdentifier): array
    {
        if (empty($persistenceIdentifier)) {
            throw new \InvalidArgumentException('$persistenceIdentifier must not be empty.', 1477071939);
        }

        $references = [];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $referenceRows = $this->databaseService->getReferencesByPersistenceIdentifier($persistenceIdentifier);
        foreach ($referenceRows as &$referenceRow) {
            $record = $this->getRecord($referenceRow['tablename'], $referenceRow['recuid']);
            if (!$record) {
                continue;
            }
            $pageRecord = $this->getRecord('pages', $record['pid']);
            $urlParameters = [
                'edit' => [
                    $referenceRow['tablename'] => [
                        $referenceRow['recuid'] => 'edit',
                    ],
                ],
                'returnUrl' => $this->getModuleUrl('web_FormFormbuilder'),
            ];

            $references[] = [
                'recordPageTitle' => is_array($pageRecord) ? $this->getRecordTitle('pages', $pageRecord) : '',
                'recordTitle' => $this->getRecordTitle($referenceRow['tablename'], $record, true),
                'recordIcon' => $iconFactory->getIconForRecord($referenceRow['tablename'], $record, IconSize::SMALL)->render(),
                'recordUid' => $referenceRow['recuid'],
                'recordEditUrl' => $this->getModuleUrl('record_edit', $urlParameters),
            ];
        }
        return $references;
    }

    /**
     * Check if a given $templatePath for a given $prototypeName is valid
     * and accessible.
     *
     * Valid template paths has to be configured within
     * formManager.selectablePrototypesConfiguration.[('identifier':  $prototypeName)].newFormTemplates.[('templatePath': $templatePath)]
     */
    protected function isValidTemplatePath(string $prototypeName, string $templatePath): bool
    {
        $isValid = false;
        foreach ($this->formSettings['formManager']['selectablePrototypesConfiguration'] as $prototypesConfiguration) {
            if ($prototypesConfiguration['identifier'] !== $prototypeName) {
                continue;
            }
            foreach ($prototypesConfiguration['newFormTemplates'] as $templatesConfiguration) {
                if ($templatesConfiguration['templatePath'] !== $templatePath) {
                    continue;
                }
                $isValid = true;
                break;
            }
        }

        $templatePath = GeneralUtility::getFileAbsFileName($templatePath);
        if (!is_file($templatePath)) {
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * Init ModuleTemplate and register document header buttons
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request, int $page, string $searchTerm): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Create new
        $addFormButton = $buttonBar->makeLinkButton()
            ->setDataAttributes(['identifier' => 'newForm'])
            ->setHref('#')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formManager.create_new_form'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));
        $buttonBar->addButton($addFormButton, ButtonBar::BUTTON_POSITION_LEFT);

        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($this->request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Shortcut
        $arguments = [];
        if ($searchTerm) {
            $arguments['tx_form_web_formformbuilder']['searchTerm'] = $searchTerm;
            $arguments['tx_form_web_formformbuilder']['controller'] = 'FormManager';
        }
        if ($page > 1) {
            $arguments['tx_form_web_formformbuilder']['page'] = $page;
            $arguments['tx_form_web_formformbuilder']['controller'] = 'FormManager';
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_FormFormbuilder')
            ->setArguments($arguments)
            ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:module.shortcut_name'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        return $moduleTemplate;
    }

    /**
     * Returns a form identifier which is the lower cased form name.
     */
    protected function convertFormNameToIdentifier(string $formName): string
    {
        $csConverter = GeneralUtility::makeInstance(CharsetConverter::class);

        $formName = \Normalizer::normalize($formName) ?: $formName;
        $formIdentifier = $csConverter->specCharsToASCII('utf-8', $formName);
        $formIdentifier = (string)preg_replace('/[^a-zA-Z0-9-_]/', '', $formIdentifier);
        $formIdentifier = lcfirst($formIdentifier);
        return $formIdentifier;
    }

    /**
     * Wrapper used for unit testing.
     */
    protected function getRecord(string $table, int $uid): ?array
    {
        return BackendUtility::getRecord($table, $uid);
    }

    /**
     * Wrapper used for unit testing.
     */
    protected function getRecordTitle(string $table, array $row, bool $prep = false): string
    {
        return BackendUtility::getRecordTitle($table, $row, $prep);
    }

    /**
     * Wrapper used for unit testing.
     */
    protected function getModuleUrl(string $moduleName, array $urlParameters = []): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute($moduleName, $urlParameters);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
