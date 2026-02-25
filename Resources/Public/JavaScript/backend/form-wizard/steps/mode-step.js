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
import{html as s}from"lit";import{live as l}from"lit/directives/live.js";import{unsafeHTML as c}from"lit/directives/unsafe-html.js";import t from"~labels/form.form_manager_javascript";var a;(function(i){i.Blank="blank",i.Predefined="predefined"})(a||(a={}));class n{constructor(e){this.context=e,this.key="mode",this.title=t.get("formManager.newFormWizard.step1.progressLabel"),this.autoAdvance=!0,this.selectedMode=null,this.modes=[{key:a.Blank,label:t.get("formManager.blankForm.label"),description:t.get("formManager.blankForm.description"),iconIdentifier:"apps-pagetree-page-default"},{key:a.Predefined,label:t.get("formManager.predefinedForm.label"),description:t.get("formManager.predefinedForm.description"),iconIdentifier:"form-page"}]}isComplete(){return this.getValue()!==null}render(){return this.getValue()==null&&this.modes.length>0&&this.setValue(this.modes[0].key),s`<div class=form-mode-selection><div class=form-check-card-container>${this.modes.map(e=>s`<div class="form-check form-check-type-card"><input class=form-check-input type=radio name=${this.key} id=mode-${e.key} value=${e.key} .checked=${l(this.getValue()===e.key)} @change=${()=>this.setValue(e.key)}> <label class=form-check-label for=mode-${e.key}><span class=form-check-label-header> <typo3-backend-icon identifier=${e.iconIdentifier} size=medium></typo3-backend-icon> ${e.label} </span> <span class=form-check-label-body>${c(e.description)} </span></label></div>`)}</div></div>`}reset(){this.setValue(null),this.context.clearStoreData(this.key)}getValue(){return this.selectedMode}setValue(e){this.selectedMode=e,this.context.wizard.requestUpdate()}beforeAdvance(){this.context.setStoreData(this.key,this.getValue())}getSummaryData(){const e=this.context.getStoreData(this.key);if(!e)return[];const r=this.modes.find(o=>o.key===e);return r?[{label:t.get("formManager.newFormWizard.step.modes.summary.title"),value:s`<typo3-backend-icon identifier=${r.iconIdentifier} size=small class=me-1></typo3-backend-icon>${r.label}`}]:[]}}export{a as MODE,n as ModeStep,n as default};
