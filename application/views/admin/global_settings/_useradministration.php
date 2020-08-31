<?php
/**
 * @var $sDataPolicy
 * @var $sLegalNotice
 */

?>


<div class="row">
    <div class="col-sm-12">
        <div class="form-group">
            <label class="control-label"
                for='sendadmincreationemail'><?php eT("Send email to new user administrators:"); ?></label>
            <div>
                <?php $this->widget(
                    'yiiwheels.widgets.switch.WhSwitch',
                    array(
                        'name' => 'sendadmincreationemail',
                        'htmlOptions' => array(
                            'class' => 'custom-data bootstrap-switch-boolean',
                            'uncheckValue' => false,
                        ),
                        
                        'value' => isset($sSendAdminCreationEmail) ? $sSendAdminCreationEmail : 0,
                        'onLabel' => gT('On'),
                        'offLabel' => gT('Off')
                    ));
                ?>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <label class="control-label"
               for='showpasswordinadmincreationemail'><?php eT("Show password in admin creation email:"); ?></label>
        <div>
            <?php $this->widget(
                'yiiwheels.widgets.switch.WhSwitch',
                array(
                    'name' => 'showpasswordinadmincreationemail',
                    'htmlOptions' => array(
                        'class' => 'custom-data bootstrap-switch-boolean',
                        'uncheckValue' => false,
                    ),
                    'value' => isset($sShowPasswordInAdminCreationEmail) ? $sShowPasswordInAdminCreationEmail : 0,   
                    'onLabel' => gT('On'),
                    'offLabel' => gT('Off')
                ));
            ?>
        </div>
    </div>
</div>
<div class="ls-space margin top-15">
    <div class="row">
        <div class="col-sm-12 col-lg-10">
            <!-- data policy -->
            <div class="form-group">
                <label class=" control-label"
                       for='admincreationemailtemplate'><?php eT("Admin creation email template:"); ?></label>
                <div class="">
                    <div class="htmleditor input-group">
                        <?php echo CHtml::textArea("admincreationemailtemplate",$sAdminCreationEmailTemplate, ['class' => 'form-control', 'cols' => '80', 'rows' => '20', 'id' => "admincreationemailtemplate"]); ?>
                        <?php echo getEditor("admincreationemailtemplate", "adminemailtemplate", "[" . gT("Admin email template:", "js") . "]"); ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>
