<?php

namespace Foolz\FoolFuuka\Theme\Yotsubatwo\Partial;

class ToolsReplyBox extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $backend_vars = $this->getBuilderParamManager()->getParam('backend_vars');
        $radix = $this->getBuilderParamManager()->getParam('radix');
        $user_name = $this->getBuilderParamManager()->getParam('user_name');
        $user_pass = $this->getBuilderParamManager()->getParam('user_pass');
        $user_email = $this->getBuilderParamManager()->getParam('user_email');
        $thread_id = $this->getBuilderParamManager()->getParam('thread_id', 0);
        $reply_errors = $this->getBuilderParamManager()->getParam('reply_errors', false);
        $form = $this->getForm();

        \Foolz\Plugin\Hook::forge('foolfuuka.themes.default_after_op_open')->setObject($this)->setParam('board', $radix)->execute(); ?>

        <?= $form->open(['enctype' => 'multipart/form-data', 'onsubmit' => 'fuel_set_csrf_token(this);', 'action' => $this->getUri()->create([$radix->shortname, 'submit'])]) ?>
        <?= $form->hidden('csrf_token', $this->getSecurity()->getCsrfToken()); ?>
        <?= $form->hidden('reply_numero', isset($thread_id)?$thread_id:0, array('id' => 'reply_numero')) ?>
        <?= isset($backend_vars['last_limit']) ? $form->hidden('reply_last_limit', $backend_vars['last_limit'])  : '' ?>

        <table id="reply">
            <tbody>
            <tr>
                <td><?= _i('Name') ?></td>
                <td><?php
                    echo $form->input([
                        'name' => 'name',
                        'id' => 'reply_name_yep',
                        'style' => 'display:none'
                    ]);

                    echo $form->input([
                        'name' => 'reply_bokunonome',
                        'id' => 'reply_bokunonome',
                        'value' => $user_name
                    ]);
                    ?></td>
            </tr>
            <tr>
                <td><?= _i('E-mail') ?></td>
                <td><?php
                    echo $form->input([
                        'name' => 'email',
                        'id' => 'reply_email_yep',
                        'style' => 'display:none'
                    ]);

                    echo $form->input([
                        'name' => 'reply_elitterae',
                        'id' => 'reply_elitterae',
                        'value' => $user_email
                    ]);
                    ?></td>
            </tr>
            <tr>
                <td><?= _i('Subject') ?></td>
                <td>
                    <?php
                    echo $form->input([
                        'name' => 'reply_talkingde',
                        'id' => 'reply_talkingde',
                    ]);
                    ?>

                    <?php
                    $submit_array = [
                        'data-function' => 'comment',
                        'name' => 'reply_gattai',
                        'value' => _i('Submit'),
                        'class' => 'btn',
                    ];

                    echo $form->submit($submit_array);
                    ?>

                    <?php if (!$this->getBuilderParamManager()->getParam('disable_image_upload', false)) : ?>
                    [ <label><?php echo $form->checkbox(['name' => 'reply_spoiler', 'id' => 'reply_spoiler', 'value' => 1]) ?> Spoiler Image?</label> ]
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><?= _i('Comment') ?></td>
                <td><?php
                    echo $form->textarea([
                        'name' => 'reply',
                        'id' => 'reply_comment_yep',
                        'style' => 'display:none'
                    ]);

                    echo $form->textarea([
                        'name' => 'reply_chennodiscursus',
                        'id' => 'reply_chennodiscursus',
                        'placeholder' => (!$radix->archive && isset($thread_dead) && $thread_dead) ? _i('This thread has entered ghost mode. Your reply will be marked as a ghost post and will only affect the ghost index.') : '',
                    ]);
                    ?></td>
            </tr>

            <?php if ($this->getPreferences()->get('foolframe.auth.recaptcha2_sitekey', false)) : ?>
                <script>
                    var recaptcha2 = {
                        'enabled': true,
                        'pubkey': '<?= $this->getPreferences()->get('foolframe.auth.recaptcha2_sitekey') ?>'
                    };
                </script>
                <tr><td><?= _i('Verification') ?></td>
                <td class="recaptcha_widget" style="display:none"></td></tr>
                <noscript>
                    <tr><td><?= _i('Verification') ?></td>
                    <td><div><p><?= e(_i('You might be a bot! Enter a reCAPTCHA to continue.')) ?></p></div>
                        <div style="width: 302px; height: 422px; position: relative;">
                            <div style="width: 302px; height: 422px; position: absolute;">
                                <iframe src="https://www.google.com/recaptcha/api/fallback?k=<?= $this->getPreferences()->get('foolframe.auth.recaptcha2_sitekey') ?>" frameborder="0" scrolling="no" style="width: 302px; height:422px; border-style: none;"></iframe>
                            </div>
                        </div>
                        <div style="width: 300px; height: 60px; border-style: none;bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px;background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
                                        <textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 40px; border: 1px solid #c1c1c1;
                                            margin: 10px 25px; padding: 0px; resize: none;"></textarea>
                        </div>
                    </td>
                    </tr>
                </noscript>
                <?php endif; ?>
                <?php if (!$this->getBuilderParamManager()->getParam('disable_image_upload', false)) : ?>
            <tr>
                <td><?= _i('File') ?></td>
                <td><?php echo $form->file(['name' => 'file_image', 'id' => 'file_image']) ?></td>
            </tr>
            <tr>
                <td><?= _i('Progress') ?></td>
                <td><div class="progress progress-info progress-striped active" style="width: 300px; margin-bottom: 2px"><div class="bar" style="width: 0%"></div></div></td>
            </tr>
                <?php endif; ?>
            <tr>
                <td><?= _i('Password') ?></td>
                <td><?=  $form->password([
                    'name' => 'reply_nymphassword',
                    'id' => 'reply_nymphassword',
                    'value' => $user_pass,
                    'required' => 'required'
                ]);
                    ?> <span style="font-size: smaller;">(Password used for file deletion)</span>
                </td>
            </tr>

                <?php
                $postas = ['N' => _i('User')];

                if ($this->getAuth()->hasAccess('comment.mod_capcode')) $postas['V'] = _i('Verified');
                if ($this->getAuth()->hasAccess('comment.mod_capcode')) $postas['M'] = _i('Moderator');
                if ($this->getAuth()->hasAccess('comment.admin_capcode')) $postas['A'] = _i('Administrator');
                if ($this->getAuth()->hasAccess('comment.dev_capcode')) $postas['D'] = _i('Developer');
                if ($this->getAuth()->hasAccess('comment.admin_capcode')) $postas['F'] = _i('Founder');
                if ($this->getAuth()->hasAccess('comment.admin_capcode')) $postas['G'] = _i('Manager');
                if (count($postas) > 1) :
                    ?>
                <tr>
                    <td><?= _i('Post as') ?></td>
                    <td><?= $form->select('reply_postas', 'User', $postas, array('id' => 'reply_postas')); ?></td>
                </tr>
                    <?php endif; ?>

                <?php if ($radix->getValue('posting_rules')) : ?>
            <tr class="rules">
                <td></td>
                <td>
                    <?php
                    echo \Foolz\FoolFrame\Model\Markdown::parse($radix->getValue('posting_rules'));
                    ?>
                </td>
            </tr>
            <tr class="rules">
                <td colspan="2">
                    <div id="reply_ajax_notices"></div>
                    <?php if (isset($reply_errors)) : ?>
                    <span style="color: red"><?= $reply_errors ?></span>
                    <?php endif; ?>
                </td>
            </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?= $form->close() ?>
    <?php
    }
}
