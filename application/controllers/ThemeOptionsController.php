<?php

/**
 * @class ThemeOptionsController
 */
class ThemeOptionsController extends LSBaseController
{
    /**
     * It's import to have the accessRules set (security issue).
     * Only logged in users should have access to actions. All other permissions
     * should be checked in the action itself.
     *
     * @return array
     */
    public function accessRules()
    {
        return [
            [
                'allow',
                'actions' => [],
                'users'   => ['*'], //everybody
            ],
            [
                'allow',
                'actions' => ['view'],
                'users'   => ['@'], //only login users
            ],
            ['deny'], //always deny all actions not mentioned above
        ];
    }

    /**
     * This part comes from _renderWrappedTemplate
     * 
     * @param string $view Name of View
     * 
     * @return bool
     */
    protected function beforeRender($view) : bool 
    {
        return parent::beforeRender($view);
    }

    /**
     * Displayed a particular Model.
     * 
     * @param int $id ID of model.
     * 
     * @return void
     */
    public function actionViewModel(int $id): void
    {
        if (Permission::model()->hasGlobalPermission('templates', 'read')) {
            $this->render(
                'themeoptions',
                array('model' => $model)
            );
            return;
        }
        Yii::app()->setFlashMessage(
            gT("We are sorry but you don't have permissions to do this"),
            'error'
        );
        $this->getController()->redirect(App()->createUrl("/admin"));
    }

    /**
     * Create a new model.
     * If creation is sucessful, the browser will be redirected to the 'view' page.
     * 
     * @return void
     */
    public function actionCreate(): void
    {
        if (Permission::model()->hasGlobalPermission('template', 'update')) {
            $model = new TemplateOptions();

            if (isset($_POST['TemplateOptions'])) {
                $model->attributes = $_POST['TemplateOptions'];

                if ($model->save()) {
                    $this->getController()->redirect(
                        array('themeOptions/update/id/', $model->id)
                    );
                }

                $this->render(
                    'create',
                    array(
                        'model' => $model,
                    )
                );
            } else {
                Yii::app()->setFlashMessage(
                    gt("We are sorry but you don't have permissions to do this."),
                    'error'
                );
                $this->getController()->redirect(array("themeOptions"));
            }
        }
    }

    /**
     * Resets all selected themes from massive action.
     * 
     * @return void
     * @throws Exeption
     */
    public function actionResetMultiple() : void
    {
        $aTemplates = json_decode(App()->request->getPost('sItems'));
        $gridid = App()->request->getPost('gridvalue');
        $aResults = array();

        if (Permission::model()->hasGlobalPermission('templates', 'updates')) {
            foreach ($aTemplates as $template) {
                if ($gridid == 'questionthemes-grid') {
                    $templatename = $model->name;
                    $templatefolder = $model->xml_path;
                    $aResults[$template]['title'] = $templatename;
                    $sQuestionThemeName = $model->importManifest($templatefolder);
                    $aResults[$template]['result'] = isset($sQuestionThemeName) ? true : false;
                } elseif ($gridid == 'themeoptions-grid') {
                    $templatename = $model->template_name;
                    $aResults[$template]['title'] = $templatename;
                    $aResults[$template]['result'] = TemplateConfiguration::uninstall($templatename);
                    TemplateManifest::importManifest($templatename);
                }
            }

            //set Modal table labels
            $tableLabels = array(gT('Template id'),gT('Template name') ,gT('Status'));

            App()->getController()->renderPartial(
                'ext.admin.survey.ListSurveysWidget.views.massive_actions._action_results', 
                array
                (
                    'aResults'     => $aResults,
                    'successLabel' => gT('Has been reset'),
                    'tableLabels'  => $tableLabels   
                )
            );
        } else {
            App()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
        }
    }

    /**
     * Uninstalls all selected themes from massive action.
     * 
     * @return void
     * @throws Exception
     */
    public function actionUninstallMultiple() : void
    {
        $aTemplates = json_decode(App()->request->getPost('sItems'));
        $gridid = App()->request->getPost('grididvalue');
        $aResults = array();

        if (Permission::model()->hasGlobalPermission('templates', 'update')) {
            foreach ($aTemplates as $template) {
                $model = $this->loadModel($template, $gridid);

                if ($gridid == 'questionthemes-grid') {
                    $aResults[$template]['title'] = $model->name;
                    $templatename = $model->name;
                    $aResults[$template]['title'] = $templatename;
                    $aUninstallResult = QuestionTheme::uninstall($model);
                    $aResults[$template]['result'] = isset($aUninstallResult['result']) ? $aUninstallResult['result'] : false;
                    $aResults[$template]['error'] = isset($aUninstallResult['error']) ? $aUninstallResult['error'] : null;

                } elseif ($gridid == 'themeoptions-grid') {
                    $aResults[$template]['title'] = $model->template_name;
                    $templatename = $model->template_name;
                    $aResults[$template]['title'] = $templatename;
                    if (!Template::hasInheritance($templatename)) {
                        if ($templatename != getGlobalSetting('defaulttheme')) {
                            $aResults[$template]['result'] = TemplateConfiguration::uninstall($templatename);
                        } else {
                            $aResults[$template]['result'] = false;
                            $aResults[$template]['error'] = gT('Error! You cannot uninstall the default template.');
                        }

                    } else {
                        $aResults[$template]['result'] = false;
                        $aResults[$template]['error'] = gT('Error! Some theme(s) inherit from this theme');
                    }
                }
            }
            //set Modal table labels
            $tableLabels= array(gT('Template id'),gT('Template name') ,gT('Status'));

            App()->getController()->renderPartial(
                'ext.admin.survey.ListSurveysWidget.views.massive_actions._action_results',
                array
                (
                    'aResults'     => $aResults,
                    'successLabel' => gT('Uninstalled'),
                    'tableLabels'  => $tableLabels
                )
            );
            
        } else {
            App()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
        }
    }

    /**
     * Renders selected Items for massive action modal.
     * 
     * @return void
     * @throws CHttpException
     * @throws CEcxeption
     */
    public function actionSelectedItems() : void
    {
        $aTemplates = json_decode(App()->request->getPost('$oCheckedItems'));
        $aResults = [];
        $gridid = App()->request->getParam('$grididvalue');

        foreach ($aTemplates as $template) {
            $aResults[$template]['title'] = '';
            $model = $this->loadModel($template, $gridid);

            if ($gridid == 'questionthemes-grid') {
                $aResults[$template]['title'] = $model->name;
            } elseif ($gridid == 'themeoptions-grid') {
                $aResults[$template]['title'] = $model->template_name;
            }

            $aResults[$template]['result'] = gT('Selected');
        }
        //set Modal table labels
        $tableLabels= array(gT('Template id'),gT('Template name') ,gT('Status'));

        App()->getController()->renderPartial(
            'ext.admin.grid.MassiveActionsWidget.views._selected_items',
            array(
                'aResults'     => $aResults,
                'successLabel' => gT('Selected'),
                'tableLabels'  => $tableLabels,
            )
        );        
    }

    /**
     * Updates a particular model (globally).
     * If update is successful, the browser will be redirected to the 'view' page.
     * 
     * @param integer $id ID of the model
     * 
     * @return void
     * @throws CException
     * @throws CHttpException
     */
    public function actionUpdate(int $id) : void
    {
        $model = $this->loadModel($id);

        if (Permission::model()->hasTemplatePermission($model->template_name, 'update')) {
            $model = $this->turnAjaxmodeOffAsDefault($model);
            $model->save();

            if (isset($_POST['TemplateConfiguration'])) {
                $model->attributes = $_POST['TemplateConfiguration'];
                if ($model->save()) {
                    Yii::app()->user->setFlash('success', gT('Theme options saved.'));
                    $this->getController()->redirect(array('themeOptions/update/id/'.$model->id));
                }
            }
            $this->updateCommon($model);
        } else {
            Yii::app()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
            $this->getController()->redirect(Yii::app()->getController()->createUrl("themeOptions"));
        }
    }

    /**
     * This method turns ajaxmode off as default.
     * 
     * @param TemplateConfiguration $templateConfiguration Configuration of Template
     * 
     * @return TemplateConfiguration
     */
    private function turnAjaxmodeOffAsDefault(TemplateConfiguration $templateConfiguration) : TemplateConfiguration
    {
        $attributes = $templateConfiguration->getAttributes();
        $hasOptions = isset($attributes['options']);
        if ($hasOptions) {
            $options = $attributes['options'];
            $optionsJSON = json_decode($options, true);

            if ($options !== 'inherit' && $optionsJSON !== null) {
                $ajaxModeOn  = (!empty($optionsJSON['ajaxmode']) && $optionsJSON['ajaxmode'] == 'on');
                if ($ajaxModeOn) {
                    $optionsJSON['ajaxmode'] = 'off';
                    $options = json_encode($optionsJSON);
                    $templateConfiguration->setAttribute('options', $options);
                }
            } else {
                // todo: If its inherited do something else and set pageOptions to '' cause this is rendering string and this is not good. wee need the
                // todo: json
            }
        }
        return $templateConfiguration;
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * 
     * @param integer $sid ID of model
     * 
     * @return void
     */
    public function actionUpdateSurvey(int $sid) : void
    {
        if (Permission::model()->hasGlobalPermission('templates', 'update') ||
            Permission::model()->hasSurveyPermission($sid,'surveysettings','update'))
            {
                // Did we really need hasGlobalPermission template ? We are inside survey : hasSurveyPermission only seem better
                $model = TemplateConfiguration::getInstance(null, null, $sid);

                // turn ajaxmode off as default behavior
                $model = $this->turnAjaxmodeOffAsDefault($model);
                $model->save();

                if (isset($_POST['TemplateConfiguration'])) {
                    $model->attributes = $_POST['TemplateConfiguration'];
                if ($model->save()) {
                    Yii::app()->user->setFlash('success', gT('Theme options saved.'));
                    $this->getController()->redirect(Yii::app()->getController()->createUrl("themeOptions/updateSurvey", ['surveyid'=>$sid, 'sid'=>$sid]));
                }
            }
            $this->updateCommon($model, $sid);
        } else {
            Yii::app()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
            $this->getController()->redirect(array('admin/survey/sa/view/surveyid/'.$sid));
        }
    }

    /**
     * Updates particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * 
     * @param integer $id   ID of model.
     * @param integer $gsid ?
     * @param null    $l    ?
     * 
     * @return void
     */
    public function actionUpdateSurveyGroup(int $id = null, int $gsid, $l = null) : void
    {
        if (Permission::model()->hasGlobalPermission('templates', 'update')) {
            // @todo : review permission : template permission or group permission ?
            $sTemplateName = $id !== null ? TemplateConfiguration::model()->findByPk($id)->template_name : null;
            $model = TemplateConfiguration::getInstance($sTemplateName, $gsid);

            if ($model->bJustCreated === true && $l === null) {
                $this->getController()->redirect(App()->getController()->createUrl("themeOptions/updateSurveyGroup/", ['id'=>$id, 'gsid'=>$gsid, 'l'=>1]));
            }

            if (isset($_POST['TemplateConfiguration'])) {
                $model = TemplateConfiguration::getInstance($_POST['TemplateConfiguration']['template_name'], $gsid);
                $model->attributes = $_POST['TemplateConfiguration'];
                if ($model->save()) {
                    App()->user->setFlash('success', gT('Theme options saved.'));
                    $this->getController()->redirect(App()->getController()->createUrl("/admin/surveysgroups/sa/update/", ['id'=>$gsid]));
                }
            }

            $this->updateCommon($model);
        } else {
            App()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
            $this->getController()->redirect(App()->getController()->createUrl("/admin/surveysgroups/sa/update/", ['id'=>$gsid]));
        }
    }

    /**
     * Sets admin theme.
     * 
     * @param string $sAdminTheme Admin theme
     * 
     * @return void
     */
    public function actionSetAdminTheme(string $sAdminTheme) : void
    {
        if (!Permission::model()->hasGlobalPermission('settings', 'update')) {
            App()->setFlashMessage(gt("We are sorry but you don't have permissions to do this."), 'error');
            $this->getController()->redirect(array('/admin'));
        }

        $sAdmintheme = sanitize_paranoid_string($sAdminThemeName);
        SettingGlobal::setSetting('admintheme', $sAdmintheme);
        $this->getController()->redirect(App()->getController()->createUrl("themeOptions#adminthemes"));
    }

    /**
     * Lists all models.
     * 
     * @return void
     */
    public function actionIndex() : void
    {
        if (Permission::model()->hasGlobalPermission('templates', 'read')) {
            $aData = array();
            $oSurveyTheme = new TemplateConfiguration();
            $aData['oAdminTheme']  = new AdminTheme();
            $aData['oQuestionTheme'] = new QuestionTheme;
            $canImport = true;
            $importErrorMessage = null;

            if (!is_writable(App()->getConfig('tempdir'))) {
                $canImport = false;
                $importErrorMessage = gT("The template upload directory doesn't exist or is not writable.");
            } else if (!is_writable(App()->getConfig('userthemerootdir'))) {
                $canImport = false;
                $importErrorMessage = gT("Some directories are not writable. Please change the folder permissions for /tmp and /upload/themes in order to enable this option.");
            } else if (!function_exists("zip_open")) {
                $canImport = false;
                $importErrorMessage = gT("You do not have the required ZIP library installed in PHP.");
            }

            /// FOR GRID View
            $filterForm = App()->request->getPost('TemplateConfiguration', false);
            if ($filterForm) {
                $oSurveyTheme->setAttributes($filterForm, false);
                if (array_key_exists('template_description', $filterForm)) {
                    $oSurveyTheme->template_description = $filterForm['template_description'];
                }
                if (array_key_exists('template_type', $filterForm)) {
                    $oSurveyTheme->template_type = $filterForm['template_type'];
                }
                if (array_key_exists('template_extends', $filterForm)) {
                    $oSurveyTheme->template_extends = $filterForm['template_extends'];
                }
            }

            $filterForm = App()->request->getPost('QuestionTheme', false);
            if ($filterForm) {
                $aData['oQuestionTheme']->setAttributes($filterForm, false);
                if (array_key_exists('description', $filterForm)) {
                    $aData['oQuestionTheme']->description = $filterForm['description'];
                }
                if (array_key_exists('core_theme', $filterForm)) {
                    $aData['oQuestionTheme']->core_theme = $filterForm['core_theme'] == '1' || $filterForm['core_theme'] == '0' ? intval($filterForm['core_theme']) : '';
                }
                if (array_key_exists('extends', $filterForm)) {
                    $aData['oQuestionTheme']->extends = $filterForm['extends'];
                }
            }

            // Page size
            if (App()->request->getParam('pageSize')) {
                App()->user->setState('pageSizeTemplateView', (int) App()->request->getParam('pageSize'));
            }

            $aData['oSurveyTheme'] = $oSurveyTheme;
            $aData['canImport']  = $canImport;
            $aData['importErrorMessage']  = $importErrorMessage;
            $aData['pageSize'] = App()->user->getState('pageSizeTemplateView', App()->params['defaultPageSize']); // Page size

            $this->render('index', $aData);
        } else {
            App()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
            $this->getController()->redirect(App()->createUrl("/admin"));
        }
    }

    /**
     * Manages all models.
     * 
     * @return void
     */
    public function actionAdmin() : void
    {
        if (Permission::model()->hasGlobalPermission('templates', 'read')) {
            $model = new TemplateOptions('search');
            $model->unsetAttributes(); // clear any default values
            if (isset($_GET['TemplateOptions'])) {
                $model->attributes = $_GET['TemplateOptions'];
            }

            $this->render(
                'admin',
                array(
                    'model'=>$model,
                )
            );
        } else {
            App()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
            $this->getController()->redirect(App()->createUrl("/admin"));
        }
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, and HTTP exception will be raised.
     * 
     * @param int $id     ID
     * @param int $gridid Grid ID
     * 
     * @return QuestionTheme | TemplateConfiguration | null
     */
    public function loadModel(int $id, int $gridid = null)
    {
        if ($gridid == 'questionthemes-grid') {
            $model = QuestionTheme::model()->findByPk($id);
        } else {
            $model = TemplateConfiguration::model()->findByPk($id);
        }
        if ($model === null) {
            throw new CHttpException(404, 'The requested page does not exist.');
        }

        return $model;
    }

    /**
     * Import or install the Theme Configuration into the database.
     * 
     * @throws Exception
     * @return void
     */
    public function importManifest()
    {
        $templatename = App()->request->getPost('templatename');
        $theme = App()->request->getPost('theme');
        if (Permission::model()->hasGlobalPermission('templates', 'update')) {
            if ($theme == 'questiontheme') {
                $templateFolder = App()->request->getPost('templatefolder');
                $questionTheme = new QuestionTheme();
                $themeName = $questionTheme->importManifest($templateFolder);
                if (isset($themeName)) {
                    App()->setFlashMessage(sprintf(gT('The Question theme "%s" has been sucessfully installed'), "$themeName"), 'success');
                } else {
                    App()->setFlashMessage(sprintf(gT('The Question theme "%s" could not be installed'), $themeName), 'error');
                }
                $this->getController()->redirect(array("themeOptions#questionthemes"));
            } else {
                TemplateManifest::importManifest($templatename);
                $this->getController()->redirect(array("themeOptions#surveythemes"));
            }
        } else {
            App()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
            $this->getController()->redirect(array("themeOptions"));
        }
    }

    /**
     * Uninstall.
     * 
     * @return void
     */
    public function actionUninstall() : void
    {
        $templatename = App()->request->getPost('templatename');
        if (Permission::model()->hasGlobalPermission('templates', 'update')) {
            if (!Template::hasInheritance($templatename)) {
                TemplateConfiguration::uninstall($templatename);
            } else {
                App()->setFlashMessage(
                    sprintf(
                        gT("You can't uninstall template '%s' because some templates inherit from it."),
                        $templatename
                    ),
                    'error'
                );
            }
        } else {
            App()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
        }

        $this->getController()->redirect(array("themeOptions"));
    }

    /**
     * @param integer $gsid ID
     * 
     * @return void
     * 
     * @throws Exception
     */
    public function actionReset(int $gsid) : void
    {
        $templatename = App()->request->getPost('templatename');
        if (Permission::model()->hasGlobalPermission('templates', 'update')) {
            TemplateConfiguration::uninstall($templatename);
            TemplateManifest::importManifest($templatename);
            App()->setFlashMessage(sprintf(gT("The theme '%s' has been reset."), $templatename), 'success');
            $this->getController()->redirect(array("themeOptions"));
        } else {
            App()->setFlashMessage(gT("We are sorry but you don't have permissions to do this."), 'error');
            $this->getController()->redirect(
                App()->getController()->createUrl("/admin/surveysgroups/sa/update/", ['id'=>$gsid])
            );
        }
    }

    /**
     * Performs the AJAX validation.
     * 
     * @param TemplateOptions $model Model to be validated.
     * 
     * @return void
     */
    public function actionPerformAjaxValidation(TemplateOptions $model) : void
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'template-options-form') {
            echo CActiveForm::validate($model);
            App()->end();
        }
    }

    /**
     * Preview Tag.
     * 
     * @return string | string[] | null
     * @throws CException
     */
    public function actionGetPreviewTag()
    {
        $templatename = App()->request->getPost('templatename');
        $oTemplate = TemplateConfiguration::getInstanceFromTemplateName($templatename);
        $previewTag = $oTemplate->getPreview();
        return App()->getController()->renderPartial(
            '/admin/super/_renderJson',
            ['data' => ['image' =>  $previewTag]],
            false,
            false
        );
    }

    /**
     * Updates Common.
     * 
     * @param TemplateConfiguration $model Template Configuration
     * @param int                   $sid   Survey ID
     * @param int                   $gsid  Survey Group ID
     * 
     * @return void
     */
    private function updateCommon(TemplateConfiguration $model, int $sid = null, int $gsid = null) : void
    {
         /* init the template to current one if option use some twig function (imageSrc for example) mantis #14363 */
         $oTemplate = Template::model()->getInstance($model->template_name, $sid, $gsid);

         $oModelWithInheritReplacement = TemplateConfiguration::model()->findByPk($model->id);
         $aOptionAttributes            = TemplateManifest::getOptionAttributes($oTemplate->path);
         $aTemplateConfiguration       = $oModelWithInheritReplacement->getOptionPageAttributes();
         App()->clientScript->registerPackage('bootstrap-switch', LSYii_ClientScript::POS_BEGIN);
         
        if ($aOptionAttributes['optionsPage'] == 'core') {
             App()->clientScript->registerPackage('themeoptions-core');
             $templateOptionPage = '';
        } else {
             $templateOptionPage = $oModelWithInheritReplacement->optionPage;
        }
 
        $oSimpleInheritance = Template::getInstance(
            $oModelWithInheritReplacement->sTemplateName,
            $sid,
            $gsid,
            null,
            true
        );
        
        $oSimpleInheritance->options = 'inherit';
        $oSimpleInheritanceTemplate = $oSimpleInheritance->prepareTemplateRendering(
            $oModelWithInheritReplacement->sTemplateName
        );
        $oParentOptions = (array) $oSimpleInheritanceTemplate->oOptions;
        $oParentOptions = TemplateConfiguration::translateOptionLabels($oParentOptions);
 
        $aData = array(
            'model' => $model,
            'templateOptionPage' => $templateOptionPage,
            'optionInheritedValues' => $oModelWithInheritReplacement->oOptions,
            'optionCssFiles' => $oModelWithInheritReplacement->files_css,
            'optionCssFramework' => $oModelWithInheritReplacement->cssframework_css,
            'aTemplateConfiguration' => $aTemplateConfiguration,
            'aOptionAttributes' => $aOptionAttributes,
            'sid' => $sid,
            'oParentOptions' => $oParentOptions,
            'sPackagesToLoad' => $oModelWithInheritReplacement->packages_to_load
        );
 
        if ($sid !== null) {
            $aData['topBar']['showSaveButton'] = true;
            $aData['surveybar']['buttons']['view'] = true;
            $aData['surveybar']['savebutton']['form'] = true;
            $aData['surveyid'] = $sid;
            $aData['title_bar']['title'] = gT("Survey theme options");
            $aData['subaction'] = gT("Survey theme options");
        }
 
        $this->render('themeOptions', $aData);
    }
}