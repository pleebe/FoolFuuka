<?php

namespace Foolz\FoolFuuka\Theme\Mobile\Partial;

use Foolz\FoolFuuka\Model\Comment;
use Foolz\FoolFuuka\Model\CommentBulk;
use Foolz\FoolFuuka\Model\Media;

class Board extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $board = $this->getParamManager()->getParam('board');
        $controller_method = $this->getBuilderParamManager()->getParam('controller_method', 'thread');
        $thread_id = $this->getBuilderParamManager()->getParam('thread_id', 0);
        $nreplies = $this->getBuilderParamManager()->getParam('nreplies', 0);
        $nimages = $this->getBuilderParamManager()->getParam('nimages', 0);

        $board_comment_view = $this->getBuilder()->createPartial('post', 'board_comment');

        // reusable Comment object not to create one every loop
        $comment = new Comment($this->getContext());
        $comment->setControllerMethod($controller_method);
        $media_obj = new Media($this->getContext());

        $search = array(
            '/\>[^\S ]+/s',  // strip whitespaces after tags, except space
            '/[^\S ]+\</s',  // strip whitespaces before tags, except space
            '/(\s)+/s'       // shorten multiple whitespace sequences
        );

        $replace = array(
            '>',
            '<',
            '\\1'
        );

        foreach ($board as $key => $post) :
            if (isset($post['op'])) :
                $op = $post['op'];
                $comment->setBulk($op);
                if ($op->media !== null) {
                    $media_obj->setBulk($op);
                    $media = $media_obj;
                } else {
                    $media = null;
                }
                $board_op_view = $this->getBuilder()->createPartial('post', 'board_comment');

                $board_op_view->getParamManager()->setParams([
                    'p' => $comment,
                    'p_media' => $media,
                    'modifiers' => $this->getBuilderParamManager()->getParam('modifiers', false),
                    'thread_id' => $thread_id,
                    'nreplies' => $nreplies,
                    'nimages' => $nimages,
                    'omitted' => isset($post['omitted']) && $post['omitted'] > 0 ? $post['omitted'] : 0,
                    'images_omitted' => isset($post['images_omitted']) && $post['images_omitted'] > 0 ? $post['images_omitted'] : 0,
                    'is_full_op' => true,
                ]);

                $board_op_view->doBuild();

                echo preg_replace($search, $replace, $board_op_view->build());

                $board_op_view->clearBuilt();
                $op->comment->clean();
                if ($op->media !== null) {
                    $op->media->clean();
                }

                $this->flush();
                ?>
            <?php elseif (isset($post['posts'])) : ?>
                <article class="clearfix thread">
                <?php \Foolz\Plugin\Hook::forge('foolfuuka.themes.default_after_headless_open')->setObject($this)->setParam('board', array(isset($radix) ? $radix : null))->execute(); ?>
            <?php endif; ?>

            <aside class="posts">
                <?php
                if (isset($post['posts'])) :
                    foreach ($post['posts'] as $p) {
                        /** @var CommentBulk $p */

                        $comment->setBulk($p);
                        // set the $media to null and leave the Media object in existence
                        if ($p->media !== null) {
                            $media_obj->setBulk($p);
                            $media = $media_obj;
                        } else {
                            $media = null;
                        }

                        $board_comment_view->getParamManager()->setParams([
                            'p' => $comment,
                            'p_media' => $media,
                            'modifiers' => $this->getBuilderParamManager()->getParam('modifiers', false),
                            'is_full_op' => false,
                        ]);

                        // refreshes the string
                        $board_comment_view->doBuild();

                        echo preg_replace($search, $replace, $board_comment_view->build());

                        // remove extra strings from the objects
                        $board_comment_view->clearBuilt();
                        $p->comment->clean();
                        if ($p->media !== null) {
                            $p->media->clean();
                        }

                        $this->flush();
                    }
                endif; ?>
            </aside>

            <?php if ($thread_id !== 0) : ?>
            <div class="js_hook_realtimethread"></div>
            <?= $this->getBuilder()->isPartial('tools_reply_box') ? $this->getBuilder()->getPartial('tools_reply_box')->build() : '' ?>
        <?php endif; ?>
            <?php if (isset($post['op']) || isset($post['posts'])) : ?>
            </article>
        <?php endif; ?>
        <?php endforeach; ?>
        <article class="clearfix thread backlink_container">
            <div id="backlink" style="position: absolute; top: 0; left: 0; z-index: 5;"></div>
        </article>
        <?php
    }
}
