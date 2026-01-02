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
import{html as s}from"lit";import{live as o}from"lit/directives/live.js";import{unsafeHTML as n}from"lit/directives/unsafe-html.js";import a from"~labels/form.form_manager_javascript";class r{constructor(e){this.context=e,this.key="storage",this.title=a.get("formManager.newFormWizard.step.storages.progressLabel"),this.autoAdvance=!0,this.hasDispatchedAutoAdvance=!1,this.selectedStorage=null}isComplete(){return this.getValue()!==null}render(){const e=this.context.formManager.getAccessibleStorageAdapters();let i=!1;return this.getValue()==null&&e.length>0&&(this.setValue(e[0]),e.length===1&&(i=!0)),i&&!this.hasDispatchedAutoAdvance?(this.hasDispatchedAutoAdvance=!0,this.context.dispatchAutoAdvance(),this.context.wizard.renderLoader()):s`<h2 class=h4>${a.get("formManager.newFormWizard.step.storages.title")}</h2><p>${a.get("formManager.newFormWizard.step.storages.description")}</p><div class=form-storage-selection><div class=form-check-card-container>${e.map(t=>s`<div class="form-check form-check-type-card"><input class=form-check-input type=radio name=${this.key} id=mode-${t.typeIdentifier} value=${t.typeIdentifier} .checked=${o(this.getValue()?.typeIdentifier===t.typeIdentifier)} @change=${()=>this.setValue(t)}> <label class=form-check-label for=mode-${t.typeIdentifier}><span class=form-check-label-header> <typo3-backend-icon identifier=${t.iconIdentifier} size=medium></typo3-backend-icon> ${t.label} </span> <span class=form-check-label-body>${n(t.description)} </span></label></div>`)}</div></div>`}reset(){this.setValue(null),this.context.clearStoreData(this.key)}getValue(){return this.selectedStorage}setValue(e){this.selectedStorage=e,this.context.wizard.requestUpdate()}beforeAdvance(){this.context.setStoreData(this.key,this.getValue())}getSummaryData(){const e=this.context.getStoreData(this.key);return e?[{label:a.get("formManager.newFormWizard.step.storages.summary.title"),value:s`<typo3-backend-icon identifier=${e.iconIdentifier} size=small class=me-1></typo3-backend-icon>${e.label}`}]:[]}}export{r as StorageStep,r as default};
