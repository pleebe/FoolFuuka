<?php

namespace Foolz\FoolFuuka\Theme\FoolFuuka\Partial;

use Foolz\FoolFuuka\Model\Comment;
use Foolz\FoolFuuka\Model\Media;
use Foolz\Inet\Inet;
use Rych\ByteSize\ByteSize;

class BoardComment extends \Foolz\FoolFuuka\View\View
{
    public function toString()
    {
        $controller_method = $this->getBuilderParamManager()->getParam('controller_method', 'thread');

        $p = $this->getParamManager()->getParam('p');
        $p_media = $this->getParamManager()->getParam('p_media');

        if ($this->getParamManager()->getParam('modifiers', false)) {
            $modifiers = $this->getParamManager()->getParam('modifiers');
        }
        $is_full_op = $this->getParamManager()->getParam('is_full_op', false);
        if ($is_full_op) {
            $thread_id = $this->getParamManager()->getParam('thread_id', 0);
            $omitted = $this->getParamManager()->getParam('omitted', 0);
            $images_omitted = $this->getParamManager()->getParam('images_omitted', 0);
            $nreplies = $this->getParamManager()->getParam('nreplies', 0);
            $nimages = $this->getParamManager()->getParam('nimages', 0);
        }
        $num = $p->num . ( $p->subnum ? '_' . $p->subnum : '' );

        ?>
        <div class="<?php if ($is_full_op) : ?>thread<?php else : ?>post<?php endif; ?> stub stub_doc_id_<?= $p->doc_id ?>">
                <button class="btn-toggle-post" data-function="showPost" data-board="<?= $p->radix->shortname ?>"  data-doc-id="<?= $p->doc_id ?>" data-thread-num="<?= $p->thread_num ?>"><i class="icon-plus"></i></button>
                <?php if ($p->email && $p->email !== 'noko') : ?><a href="mailto:<?= rawurlencode($p->email) ?>"><?php endif; ?><span class="post_author"><?= $p->getNameProcessed() ?></span><?= ($p->getNameProcessed() && $p->getTripProcessed()) ? ' ' : '' ?><span class="post_tripcode"><?= $p->getTripProcessed() ?></span><?php if ($p->email && $p->email !== 'noko') : ?></a><?php endif ?>
        </div>
        <article class="<?php if ($is_full_op) : ?>clearfix thread<?php else: ?>post<?php endif; ?> doc_id_<?= $p->doc_id ?><?php if ($p->subnum > 0) : ?> post_ghost<?php endif; ?><?php if ($p->thread_num === $p->num) : ?> post_is_op<?php endif; ?><?php if ( !is_null($p_media)) : ?> has_image<?php endif; ?>" id="<?= $num ?>" data-board="<?= $p->radix->shortname ?>" data-doc-id="<?= $p->doc_id ?>" <?php if ($is_full_op) : ?>data-thread-num="<?= $p->thread_num ?>"<?php endif; ?>>
            <?php if ($is_full_op) : ?>
                <?php if ($thread_id === 0) : ?>
                    <div class="stub pull-left">
                        <button class="btn-toggle-post" data-function="hideThread" data-board="<?= $p->radix->shortname ?>" data-doc-id="<?= $p->doc_id ?>"><i class="icon-minus"></i></button>
                    </div>
                <?php else : ?>
                    <div class="pull-right" title="<?= _i('Post Count') ?> / <?= _i('File Count') ?> / <?= _i('Posters') ?>">[<?= $nreplies ?> / <?= $nimages ?> / <?= $p->getExtraData('uniqueIps') ? $p->getExtraData('uniqueIps') : '?' ?>]</div>
                <?php endif; ?>
                <?php \Foolz\Plugin\Hook::forge('foolfuuka.themes.default_after_op_open')->setObject($this)->setParam('board', $p->radix)->execute(); ?>
            <?php else: ?>
            <div class="stub pull-left">
                <button class="btn-toggle-post" data-function="hidePost" data-board="<?= $p->radix->shortname ?>" data-doc-id="<?= $p->doc_id ?>"><i class="icon-minus"></i></button>
            </div>
            <?php endif; ?>
            <?php if (!$is_full_op) : ?><div class="post_wrapper"><?php endif; ?>
                <?php if ($p_media !== null) : ?>
                    <?php if (!$is_full_op) : ?>
                <div class="post_file">
                    <span class="post_file_controls">
                    <?php if ($p_media->getMediaStatus($this->getRequest()) !== 'banned' || $this->getAuth()->hasAccess('media.see_hidden')) : ?>
                        <?php if ( !$p->radix->hide_thumbnails || $this->getAuth()->hasAccess('media.see_hidden')) : ?>
                        <a href="<?= $this->getUri()->create(((isset($modifiers['post_show_board_name']) && $modifiers['post_show_board_name']) ? '_' : $p->radix->shortname) . '/search/image/' . $p_media->getSafeMediaHash()) ?>" class="btnr parent"><?= _i('View Same') ?></a><a
                            href="http://www.google.com/searchbyimage?sbisrc=foolfuuka&image_url=<?= $p_media->getThumbLink($this->getRequest()) ?>" target="_blank" class="btnr parent">Google</a><a
                            href="http://imgops.com/<?= $p_media->getThumbLink($this->getRequest()) ?>" target="_blank" class="btnr parent">ImgOps</a><a
                            href="http://iqdb.org/?url=<?= $p_media->getThumbLink($this->getRequest()) ?>" target="_blank" class="btnr parent">iqdb</a><a
                            href="http://saucenao.com/search.php?url=<?= $p_media->getThumbLink($this->getRequest()) ?>" target="_blank" class="btnr parent">SauceNAO</a><?php if (!$p->radix->archive || $p->radix->getValue('archive_full_images')) : ?><a
                            href="<?= $p_media->getMediaDownloadLink($this->getRequest()) ?>" download="<?= $p_media->getMediaFilenameProcessed() ?>" class="btnr parent"><i class="icon-download-alt"></i></a><?php endif; ?>
                        <?php endif; ?>
                    <?php endif ?>
                    </span>
                    <?php if ($p_media->getMediaStatus($this->getRequest()) !== 'banned' || $this->getAuth()->hasAccess('media.see_banned')) : ?>
                    <?php if (mb_strlen($p_media->getMediaFilenameProcessed()) > 38) : ?>
                        <a href="<?= ($p_media->getMediaLink($this->getRequest())) ? $p_media->getMediaLink($this->getRequest()) : $p_media->getRemoteMediaLink($this->getRequest()) ?>" class="post_file_filename" rel="tooltip" title="<?= htmlspecialchars($p_media->media_filename) ?>"><?= mb_substr($p_media->getMediaFilenameProcessed(), 0, 32, 'utf-8') . ' (...)' . mb_substr($p_media->getMediaFilenameProcessed(), mb_strrpos($p_media->getMediaFilenameProcessed(), '.', 'utf-8'), null, 'utf-8') ?></a>,
                    <?php else: ?>
                        <a href="<?= ($p_media->getMediaLink($this->getRequest())) ? $p_media->getMediaLink($this->getRequest()) : $p_media->getRemoteMediaLink($this->getRequest()) ?>" class="post_file_filename" rel="tooltip" title="<?= htmlspecialchars($p_media->media_filename) ?>"><?= $p_media->getMediaFilenameProcessed() ?></a>,
                    <?php endif; ?>
                    <span class="post_file_metadata">
                        <?= ByteSize::formatBinary($p_media->media_size, 0) . ', ' . $p_media->media_w . 'x' . $p_media->media_h ?>
                    </span>
                    <?php endif; ?>
                </div>
                    <?php endif; ?>
                <div class="thread_image_box">
                    <?php if ($p_media->getMediaStatus($this->getRequest()) === 'banned') : ?>
                        <img src="<?= $this->getAssetManager()->getAssetLink('images/banned-image.png') ?>" width="150" height="150" />
                    <?php elseif ($p_media->getMediaStatus($this->getRequest()) !== 'normal'): ?>
                        <a href="<?= ($p_media->getMediaLink($this->getRequest())) ? $p_media->getMediaLink($this->getRequest()) : $p_media->getRemoteMediaLink($this->getRequest()) ?>" target="_blank" rel="noreferrer" class="thread_image_link">
                            <img src="<?= $this->getAssetManager()->getAssetLink('images/missing-image.jpg') ?>" width="150" height="150" />
                        </a>
                    <?php else: ?>
                        <a href="<?= ($p_media->getMediaLink($this->getRequest())) ? $p_media->getMediaLink($this->getRequest()) : $p_media->getRemoteMediaLink($this->getRequest()) ?>" target="_blank" rel="noreferrer" class="thread_image_link">
                            <?php if (!$this->getAuth()->hasAccess('maccess.mod') && !$p->radix->getValue('transparent_spoiler') && $p_media->spoiler) :?>
                            <div class="spoiler_box"><span class="spoiler_box_text"><?= _i('Spoiler') ?><span class="spoiler_box_text_help"><?= _i('Click to view') ?></span></div>
                            <?php else : ?>
                            <img src="<?= $p_media->getThumbLink($this->getRequest()) ?>" width="<?= $p_media->preview_w ?>" height="<?= $p_media->preview_h ?>" class="<?php if ($is_full_op): ?>thread<?php else: ?>post<?php endif; ?>_image<?= ($p_media->spoiler) ? ' is_spoiler_image' : '' ?>" data-md5="<?= $p_media->media_hash ?>" />
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                        <?php if ($is_full_op) : ?>
                            <?php if ($p_media->getMediaStatus($this->getRequest()) !== 'banned') : ?>
                                <div class="post_file" style="padding-left: 2px;<?php if ($p_media->preview_w > 149) echo 'max-width:'.$p_media->preview_w .'px;'; ?>">
                                    <?= ByteSize::formatBinary($p_media->media_size, 0) . ', ' . $p_media->media_w . 'x' . $p_media->media_h . ', '; ?><a class="post_file_filename" href="<?= ($p_media->getMediaLink($this->getRequest())) ? $p_media->getMediaLink($this->getRequest()) : $p_media->getRemoteMediaLink($this->getRequest()) ?>" target="_blank"><?= $p_media->getMediaFilenameProcessed(); ?></a>
                                </div>
                            <?php endif; ?>
                    <div class="post_file_controls">
                        <?php if ($p_media->getMediaStatus($this->getRequest()) !== 'banned' || $this->getAuth()->hasAccess('media.see_banned')) : ?>
                            <?php if ( !$p->radix->hide_thumbnails || $this->getAuth()->hasAccess('maccess.mod')) : ?>
                                <a href="<?= $this->getUri()->create($p->radix->shortname . '/search/image/' . $p_media->getSafeMediaHash()) ?>" class="btnr parent"><?= _i('View Same') ?></a><a
                                    href="http://www.google.com/searchbyimage?sbisrc=foolfuuka&image_url=<?= $p_media->getThumbLink($this->getRequest()) ?>" target="_blank"
                                    class="btnr parent">Google</a><a
                                    href="http://imgops.com/<?= $p_media->getThumbLink($this->getRequest()) ?>" target="_blank"
                                    class="btnr parent">ImgOps</a><a
                                    href="http://iqdb.org/?url=<?= $p_media->getThumbLink($this->getRequest()) ?>" target="_blank"
                                    class="btnr parent">iqdb</a><a
                                href="http://saucenao.com/search.php?url=<?= $p_media->getThumbLink($this->getRequest()) ?>" target="_blank"
                                class="btnr parent">SauceNAO</a><?php if (!$p->radix->archive || $p->radix->getValue('archive_full_images')) : ?><a
                                    href="<?= $p_media->getMediaDownloadLink($this->getRequest()) ?>" download="<?= $p_media->getMediaFilenameProcessed() ?>"
                                    class="btnr parent"><i class="icon-download-alt"></i></a><?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                        <?php endif; ?>
                </div>
                <?php endif; ?>
                <header>
                    <div class="post_data">
                        <?php if (isset($modifiers['post_show_board_name']) && $modifiers['post_show_board_name']) : ?>
                        <span class="post_show_board">/<?= $p->radix->shortname ?>/</span>
                        <?php endif; ?>

                        <?php if ($p->getTitleProcessed() !== '') : ?><h2 class="post_title"><?= $p->getTitleProcessed() ?></h2><?php endif; ?>
                        <span class="post_poster_data">
                            <?php if ($p->email && $p->email !== 'noko') : ?><a href="mailto:<?= rawurlencode($p->email) ?>"><?php endif; ?><span class="post_author"><?= $p->getNameProcessed() ?></span><?= ($p->getNameProcessed() && $p->getTripProcessed()) ? ' ' : '' ?><span class="post_tripcode"><?= $p->getTripProcessed() ?></span><?php if ($p->email && $p->email !== 'noko') : ?></a><?php endif ?>

                            <?php if ($p->getPosterHashProcessed()) : ?><span class="poster_hash">ID:<?= $p->getPosterHashProcessed() ?></span><?php endif; ?>
                            <?php if ($p->capcode !== 'N') : ?>
                                <?php if ($p->capcode === 'M') : ?><span class="post_level post_level_moderator">## <?= _i('Mod') ?></span><?php endif ?>
                                <?php if ($p->capcode === 'A') : ?><span class="post_level post_level_administrator">## <?= _i('Admin') ?></span><?php endif ?>
                                <?php if ($p->capcode === 'D') : ?><span class="post_level post_level_developer">## <?= _i('Developer') ?></span><?php endif ?>
                                <?php if ($p->capcode === 'V') : ?><span class="post_level post_level_verified">## <?= _i('Verified') ?></span><?php endif ?>
                                <?php if ($p->capcode === 'F') : ?><span class="post_level post_level_founder">## <?= _i('Founder') ?></span><?php endif ?>
                                <?php if ($p->capcode === 'G') : ?><span class="post_level post_level_manager">## <?= _i('Manager') ?></span><?php endif ?>
                            <?php endif; ?>
                        </span>
                        <span class="time_wrap">
                            <time datetime="<?= gmdate(DATE_W3C, $p->timestamp) ?>" <?php if ($p->radix->archive) : ?> title="<?= _i('4chan Time') . ': ' . $p->getFourchanDate() ?>"<?php endif; ?>><?= gmdate('D d M Y H:i:s', $p->timestamp) ?></time>
                        </span>
                        <a href="<?= $this->getUri()->create([$p->radix->shortname, $controller_method, $p->thread_num]) . '#'  . $num ?>" data-post="<?= $num ?>" data-function="highlight" title="Link to this post">No.</a><a href="<?= $this->getUri()->create([$p->radix->shortname, $controller_method, $p->thread_num]) . '#q' . $num ?>" data-post="<?= str_replace('_', ',', $num) ?>" data-function="quote" title="Reply to this post"><?= str_replace('_', ',', $num) ?></a>

                        <span class="post_type">
                            <?php if ($p->getExtraData('since4pass') !== null) : ?><i class="icon-tag" title="<?= htmlspecialchars(sprintf(_i('Pass user since %s.'), $p->getExtraData('since4pass'))) ?>"></i><?php endif ?>
                            <?php if ($p->poster_country !== null) : ?><span title="<?= e($p->poster_country_name) ?>" class="flag flag-<?= strtolower($p->poster_country) ?>"></span><?php endif; ?>
                            <?php if ($p->subnum) : ?><i class="icon-comment-alt" title="<?= htmlspecialchars(_i('This post was submitted as a "ghost" reply.')) ?>"></i><?php endif ?>
                            <?php if (isset($p_media) && $p_media->spoiler) : ?><i class="icon-eye-close" title="<?= htmlspecialchars(_i('The image in this post has been marked spoiler.')) ?>"></i><?php endif ?>
                            <?php if ($p->deleted && !$p->timestamp_expired) : ?><i class="icon-trash" title="<?= htmlspecialchars(_i('This post was prematurely deleted.')) ?>"></i><?php endif ?>
                            <?php if ($p->deleted && $p->timestamp_expired) : ?><i class="icon-trash" title="<?= htmlspecialchars(_i('This post was deleted on %s.', gmdate('M d, Y \a\t H:i:s e', $p->timestamp_expired))) ?>"></i><?php endif ?>
                            <?php if ($p->sticky) : ?><i class="icon-pushpin" title="<?= _i('This thread has been stickied.') ?>"></i><?php endif; ?>
                            <?php if ($p->locked) : ?><i class="icon-lock" title="<?= _i('This thread has been locked.') ?>"></i><?php endif; ?>
                        </span>

                        <span class="post_controls">
                            <?php if ($is_full_op): ?>
                                <a href="<?= $this->getUri()->create(array($p->radix->shortname, 'thread', $num)) ?>" class="btnr parent"><?= _i('View') ?></a><a href="<?= $this->getUri()->create(array($p->radix->shortname, $controller_method, $num)) . '#reply' ?>" class="btnr parent"><?= _i('Reply') ?></a><?= (isset($omitted) && $omitted > 50) ? '<a href="' . $this->getUri()->create($p->radix->shortname . '/last/50/' . $num) . '" class="btnr parent">' . _i('Last 50') . '</a>' : '' ?><?= ($p->radix->archive) ? '<a href="//boards.4chan.org/' . $p->radix->shortname . '/thread/' . $num . '" class="btnr parent">' . _i('Original') . '</a>' : '' ?><a href="#" class="btnr parent" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($p->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report"><?= _i('Report') ?></a><?php if ($this->getAuth()->hasAccess('maccess.mod') || !$p->radix->archive) : ?><a href="#" class="btnr parent" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($p->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="delete"><?= _i('Delete') ?></a><?php endif; ?>
                            <?php else: ?>
                                <?php if (isset($modifiers['post_show_view_button'])) : ?><a href="<?= $this->getUri()->create($p->radix->shortname . '/thread/' . $p->thread_num) . '#' . $num ?>" class="btnr parent"><?= _i('View') ?></a><?php endif; ?><a href="#" class="btnr parent" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($p->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="report"><?= _i('Report') ?></a><?php if ($p->subnum > 0 || $this->getAuth()->hasAccess('comment.passwordless_deletion') || !$p->radix->archive) : ?><a href="#" class="btnr parent" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($p->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="delete"><?= _i('Delete') ?></a><?php endif; ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </header>
                <div class="backlink_list"<?= $p->getBacklinks() ? ' style="display:block"' : '' ?>>
                    <?= _i('Quoted By:') ?> <span class="post_backlink" data-post="<?= $p->num ?>"><?= $p->getBacklinks() ? implode(' ', $p->getBacklinks()) : '' ?></span>
                </div>
                <div class="text<?php if (preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $p->getCommentProcessed())) echo ' shift-jis'; ?>">
                    <?= $p->getCommentProcessed() ?>
                </div>
                <?php if ($is_full_op) : ?><div class="thread_tools_bottom"><?php endif; ?>
                <?php if ($p_media !== null && $p_media->getMediaStatus($this->getRequest()) === 'normal' && $p->radix->getValue('display_exif') && $p_media->exif !== NULL) : ?>
                    <div class="exif">[Exif data available. <a data-function="toggleExif" data-post="<?= $p->doc_id ?>">Click here to show/hide</a>.]</div>
                    <table class="exiftable <?= $p->doc_id ?>" style="display:none;"><tbody>
                        <?php foreach ($p_media->getExifData() as $a => $b) : ?>
                            <?php if(is_object($b)) : ?>
                                <?php foreach ($b as $c => $d) : ?>
                                    <tr><td><?= htmlentities($a)," ",htmlentities($c) ?></td><td><?= htmlentities($d) ?></td></tr>
                                <?php endforeach ?>
                            <?php elseif(is_array($b)): ?>
                                <tr><td><?= htmlentities($a) ?></td><td>
                                        <?php foreach ($b as $e) : ?>
                                            <?= htmlentities($e) ?>
                                        <?php endforeach ?></td></tr>
                            <?php else: ?>
                                <tr><td><?= htmlentities($a) ?></td><td><?= htmlentities($b) ?></td></tr>
                            <?php endif; ?>
                        <?php endforeach ?>
                        </tbody></table>
                <?php endif; ?>
                <?php if ($this->getAuth()->hasAccess('maccess.mod')) :
                $poster_ip = Inet::dtop($p->poster_ip); /* only dtop once */?>
                <div class="btn-group post_mod">
                    <button class="btn btn-mini" data-function="activateModeration"><?= _i('Mod') ?><?php if ($p->poster_ip) echo ' ' . $poster_ip ?></button>
                </div>
                <div class="btn-group post_mod_controls">
                    <button class="btn btn-mini" data-post="<?= $p->doc_id ?>" data-post-id="<?= $num ?>" data-board="<?= htmlspecialchars($p->radix->shortname) ?>" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-function="editPost"><?= _i('Edit Post') ?></button>
                    <button class="btn btn-mini" data-function="addBulkMod"><?= _i('Bulk Mod') ?></button>
                    <?php if ($p->op) : ?>
                    <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->doc_id ?>" data-action="toggle_sticky"><?= _i('Toggle Sticky') ?></button>
                    <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->doc_id ?>" data-action="toggle_locked"><?= _i('Toggle Locked') ?></button>
                    <?php endif; ?>
                    <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-board-url="<?= $this->getUri()->create([$p->radix->shortname]) ?>" data-id="<?= $p->doc_id ?>" data-action="delete_post"><?= _i('Delete ' . ($p->op ? 'thread' : 'post')) ?></button>
                    <?php if ( !is_null($p_media)) : ?>
                        <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p_media->media_id ?>" data-doc-id="<?= $p->doc_id ?>" data-action="delete_image"><?= _i('Delete Image') ?></button>
                        <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p_media->media_id ?>" data-doc-id="<?= $p->doc_id ?>" data-action="ban_image"><?= _i('Ban Image') ?></button>
                        <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p_media->media_id ?>" data-doc-id="<?= $p->doc_id ?>" data-global="true" data-action="ban_image"><?= _i('Ban Image Globally') ?></button>
                    <?php endif; ?>
                    <?php if ($p->poster_ip) : ?>
                        <button class="btn btn-mini" data-function="mod" data-board="<?= $p->radix->shortname ?>" data-ip="<?= $poster_ip ?>" data-action="delete_user"><?= _i('Delete All Post By IP') ?></button>
                        <button class="btn btn-mini" data-function="ban" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-board="<?= $p->radix->shortname ?>" data-post="<?= $p->doc_id ?>" data-ip="<?= $poster_ip ?>" data-action="ban_user"><?= _i('Ban IP:') . ' ' . $poster_ip ?></button>
                        <button class="btn btn-mini" data-function="searchUser" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->doc_id ?>" data-poster-ip="<?= $poster_ip ?>"><?= _i('Search IP') ?></button>
                        <?php if ($this->getPreferences()->get('foolfuuka.sphinx.global')) : ?>
                            <button class="btn btn-mini" data-function="searchUserGlobal" data-board="<?= $p->radix->shortname ?>" data-id="<?= $p->doc_id ?>" data-poster-ip="<?= $poster_ip ?>"><?= _i('Search IP Globally') ?></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if ($p->getReports()) : ?>
                    <?php foreach ($p->getReports() as $report) : ?>
                        <div class="report_reason"><?= '<strong>' . _i('Reported Reason:') . '</strong> ' . $report->getReasonProcessed() ?>
                            <br/>
                            <div class="ip_reporter">
                                <strong><?= _i('Info:') ?></strong>
                                <?php $report_ip = Inet::dtop($report->ip_reporter); /* only dtop once */ ?>
                                <?= $report_ip ?>, <?= _i('Type:') ?> <?= $report->media_id !== null ? _i('media') : _i('post')?>, <?= _i('Time:')?> <?= gmdate('D M d H:i:s Y', $report->created) ?>
                                <div class="btn-group">
                                    <button class="btn btn-mini" data-function="ban" data-controls-modal="post_tools_modal" data-backdrop="true" data-keyboard="true" data-board="<?= $p->radix->shortname ?>" data-ip="<?= $report_ip ?>" data-action="ban_user"><?= _i('Ban IP:') . ' ' . $report_ip ?></button>
                                    <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-action="delete_report"><?= _i('Delete Report') ?></button>
                                    <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-action="delete_all_reports"><?= _i('Delete All Reports') ?></button>
                                </div>
                                <div class="btn-group" style="clear:both;">
                                    <button class="btn btn-mini" data-function="activateExtraMod"><?= _i('Extra') ?></button>
                                </div>
                                <div class="post_extra_mod" style="clear:both;">
                                    <div class="btn-group">
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-action="delete_all_report_posts"><?= _i('Delete All Reported Posts') ?></button>
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-action="delete_all_report_images"><?= _i('Delete All Reported Images') ?></button>
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-action="ban_all_report_images"><?= _i('Ban All Reported Images') ?></button>
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-global="true" data-action="ban_all_report_images"><?= _i('Globally Ban All Reported Images') ?></button>
                                    </div>
                                    <div class="btn-group">
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-ip="<?= $report_ip ?>" data-action="delete_all_reports"><?= _i('Delete All Reports From This IP') ?></button>
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-ip="<?= $report_ip ?>" data-action="delete_all_report_posts"><?= _i('Delete All Posts Reported By This IP') ?></button>
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-ip="<?= $report_ip ?>" data-action="delete_all_report_images"><?= _i('Delete All Images Reported By This IP') ?></button>
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-ip="<?= $report_ip ?>" data-action="ban_all_report_images"><?= _i('Ban All Images Reported By This IP') ?></button>
                                        <button class="btn btn-mini" data-function="mod" data-id="<?= $report->id ?>" data-board="<?= $p->radix->shortname ?>" data-ip="<?= $report_ip ?>" data-global="true" data-action="ban_all_report_images"><?= _i('Globally Ban All Images Reported By This IP') ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php endif; ?>
        <?php if ($is_full_op && isset($omitted) && $omitted > 0) : ?>
        <span class="omitted">
            <a style="display:inline-block" href="<?= $this->getUri()->create(array($p->radix->shortname, $controller_method, $p->thread_num)) ?>" data-function="expandThread" data-thread-num="<?= $p->thread_num ?>"><i class="icon icon-resize-full"></i></a>
            <span class="omitted_text">
                <span class="omitted_posts"><?= $omitted ?></span> <?= _n('post', 'posts', $omitted) ?>
                <?php if (isset($images_omitted) && $images_omitted > 0) : ?>
                    <?= _i('and') ?> <span class="omitted_images"><?= $images_omitted ?></span> <?= _n('image', 'images', $images_omitted) ?>
                <?php endif; ?>
                <?= _n('omitted', 'omitted', $omitted + $images_omitted) ?>
            </span>
        </span>
    <?php endif; ?>
        </div>
        <?php if (!$is_full_op) : ?></article><?php endif; ?>
        <?php
    }
}
