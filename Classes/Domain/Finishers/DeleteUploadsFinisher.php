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

namespace TYPO3\CMS\Form\Domain\Finishers;

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Extbase\Domain\Model\FileReference as ExtbaseFileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;

/**
 * This finisher remove the submitted files.
 * Use this e.g after the email finisher if you don't want
 * to keep the files online.
 *
 * Scope: frontend
 */
class DeleteUploadsFinisher extends AbstractFinisher
{
    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();

        $uploadFolders = [];
        $elements = $formRuntime->getFormDefinition()->getRenderablesRecursively();
        foreach ($elements as $element) {
            if (!$element instanceof FileUpload) {
                continue;
            }
            $file = $formRuntime[$element->getIdentifier()];
            if (!$file) {
                continue;
            }

            if ($file instanceof ExtbaseFileReference) {
                $file = $file->getOriginalResource();
            }
            if ($file instanceof FileReference) {
                $this->deleteFileAndCollectFolder($file, $uploadFolders);
            } elseif ($file instanceof ObjectStorage) {
                foreach ($file as $singleFile) {
                    if ($singleFile instanceof ExtbaseFileReference) {
                        $singleFile = $singleFile->getOriginalResource();
                    }
                    if ($singleFile instanceof FileReference) {
                        $this->deleteFileAndCollectFolder($singleFile, $uploadFolders);
                    }
                }
            }

        }

        $this->deleteEmptyUploadFolders($uploadFolders);
    }

    /**
     * Deletes the file and collects its parent folder for later cleanup.
     *
     * @param array<string, Folder> $uploadFolders
     */
    private function deleteFileAndCollectFolder(FileReference $file, array &$uploadFolders): void
    {
        $folder = $file->getParentFolder();
        if ($folder instanceof Folder) {
            $uploadFolders[$folder->getCombinedIdentifier()] = $folder;
        }
        $file->getStorage()->deleteFile($file->getOriginalFile());
    }

    /**
     * note:
     * TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter::importUploadedResource()
     * creates a sub-folder for file uploads (e.g. .../form_<40-chars-hash>/actual.file)
     * @param Folder[] $folders
     */
    protected function deleteEmptyUploadFolders(array $folders): void
    {
        foreach ($folders as $folder) {
            if ($this->isEmptyFolder($folder)) {
                $folder->delete();
            }
        }
    }

    protected function isEmptyFolder(Folder $folder): bool
    {
        return $folder->getFileCount() === 0
            && $folder->getStorage()->countFoldersInFolder($folder) === 0;
    }
}
