<?php

namespace Foolz\FoolFuuka\Controller\Api;

use Foolz\FoolFrame\Controller\Common;
use Foolz\FoolFrame\Model\Config;
use Foolz\FoolFrame\Model\Preferences;
use Foolz\FoolFrame\Model\Uri;
use Foolz\FoolFuuka\Model\BanFactory;
use Foolz\FoolFuuka\Model\Board;
use Foolz\FoolFuuka\Model\Comment;
use Foolz\FoolFuuka\Model\CommentBulk;
use Foolz\FoolFuuka\Model\CommentFactory;
use Foolz\FoolFuuka\Model\Media;
use Foolz\FoolFuuka\Model\MediaFactory;
use Foolz\FoolFuuka\Model\Radix;
use Foolz\FoolFuuka\Model\RadixCollection;
use Foolz\FoolFuuka\Model\ReportCollection;
use Foolz\FoolFuuka\Model\Search;
use Foolz\Inet\Inet;
use Foolz\Profiler\Profiler;
use Foolz\Theme\Builder;
use Foolz\Theme\Theme;
use Foolz\Plugin\Hook;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Chan extends Common
{
    /**
     * @var Radix
     */
    protected $radix;

    /**
     * @var Theme
     */
    protected $theme;

    /**
     * @var Builder
     */
    protected $builder = null;

    /**
     * @var Request
     */
    protected $request = null;

    /**
     * @var Response
     */
    protected $response = null;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Preferences
     */
    protected $preferences;

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var Profiler
     */
    protected $profiler;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * @var MediaFactory
     */
    protected $media_factory;

    /**
     * @var CommentFactory
     */
    protected $comment_factory;

    /**
     * @var BanFactory
     */
    protected $ban_factory;

    /**
     * @var ReportCollection
     */
    protected $report_coll;

    /**
     * @var Comment
     */
    protected $comment_obj;

    /**
     * @var Media
     */
    protected $media_obj;

    public function before()
    {
        $this->config = $this->getContext()->getService('config');
        $this->preferences = $this->getContext()->getService('preferences');
        $this->uri = $this->getContext()->getService('uri');
        $this->profiler = $this->getContext()->getService('profiler');
        $this->radix_coll = $this->getContext()->getService('foolfuuka.radix_collection');
        $this->media_factory = $this->getContext()->getService('foolfuuka.media_factory');
        $this->comment_factory = $this->getContext()->getService('foolfuuka.comment_factory');
        $this->ban_factory = $this->getContext()->getService('foolfuuka.ban_factory');
        $this->report_coll = $this->getContext()->getService('foolfuuka.report_collection');

        // this has already been forged in the foolfuuka bootstrap
        $theme_instance = \Foolz\Theme\Loader::forge('foolfuuka');

        if ($this->getQuery('theme')) {
            try {
                $theme_name = $this->getQuery('theme', $this->getCookie('theme')) ? : $this->preferences->get('foolfuuka.theme.default');
                $theme = $theme_instance->get($theme_name);
                if (!isset($theme->enabled) || !$theme->enabled) {
                    throw new \OutOfBoundsException;
                }
                $this->theme = $theme;
            } catch (\OutOfBoundsException $e) {
                $theme_name = 'foolz/foolfuuka-theme-foolfuuka';
                $this->theme = $theme_instance->get($theme_name);
            }

            $this->builder = $this->theme->createBuilder();
            $this->builder->getParamManager()->setParams([
                'context' => $this->getContext(),
                'request' => $this->getRequest()
            ]);
        }

        // convenience objects for saving some RAM
        $this->comment_obj = new Comment($this->getContext());
        $this->media_obj = new Media($this->getContext());
    }

    public function router($method)
    {
        // create response object, store request object
        $this->response = new JsonResponse();

        // enforce CORS on application level
        $this->response->headers->set('Access-Control-Allow-Origin', '*');
        $this->response->headers->set('Access-Control-Allow-Credentials', 'true');
        $this->response->headers->set('Access-Control-Allow-Methods', 'GET, HEAD, POST, PUT, DELETE, OPTIONS');
        $this->response->headers->set('Access-Control-Max-Age', '604800');

        $request = $this->getRequest();
        if (($request->getMethod() == 'GET' || $request->getMethod() == 'HEAD') && method_exists($this, 'get_'.$method)) {
            return [$this, 'get_'.$method, []];
        }

        if ($request->getMethod() == 'POST' && method_exists($this, 'post_'.$method)) {
            return [$this, 'post_'.$method, []];
        }

        return [$this, 'get_404', []];
    }

    public function setLastModified($timestamp = 0, $max_age = 0)
    {
        $this->response->headers->addCacheControlDirective('must-revalidate', true);
        $this->response->setLastModified(new \DateTime('@'.$timestamp));
        $this->response->setMaxAge($max_age);
    }

    /**
     * Commodity to check that the shortname is not wrong and return a coherent error
     */
    protected function check_board()
    {
        $board = $this->getQuery('board', $this->getPost('board', null));

        if ($board === null) {
            return false;
        }

        if (!$this->radix = $this->radix_coll->getByShortname($board)) {
            return false;
        }

        return true;
    }

    public function apify($bulk, $controller_method = 'thread')
    {
        $this->comment_obj->setBulk($bulk);
        $comment_force = [
            'getTitleProcessed',
            'getNameProcessed',
            'getEmailProcessed',
            'getTripProcessed',
            'getPosterHashProcessed',
            'getOriginalTimestamp',
            'getFourchanDate',
            'getCommentSanitized',
            'getCommentProcessed', // this is necessary also to get backlinks parsed
            'getPosterCountryNameProcessed'
        ];

        foreach ($comment_force as $value) {
            $this->comment_obj->$value();
        }

        $m = null;
        if ($bulk->media !== null) {
            $media_force = [
                'getMediaFilenameProcessed',
                'getMediaLink',
                'getThumbLink',
                'getRemoteMediaLink',
                'getMediaStatus',
                'getSafeMediaHash'
            ];

            $this->media_obj->setBulk($bulk);
            $m = $this->media_obj;

            foreach ($media_force as $value) {
                $this->media_obj->$value($this->getRequest());
            }
        }

        if ($this->builder) {
            $this->builder->getParamManager()->setParam('controller_method', $controller_method);
            $partial = $this->builder->createPartial('board_comment', 'board_comment');
            $partial->getParamManager()
                ->setParam('p', $this->comment_obj)
                ->setParam('p_media', $m);

            $bulk->comment->formatted = $partial->build();
            $partial->clearBuilt();
        }
    }

    public function get_404()
    {
        return $this->response->setData(['error' => _i('Requested resource does not exist.')])->setStatusCode(404);
    }

    public function get_boards()
    {
        return $this->get_site('boards');
    }

    public function get_archives()
    {
        return $this->get_site('archives');
    }

    public function get_site($mode = '')
    {
        $res['site'] = [
            'url' => $this->uri->base(),
            'name' => $this->preferences->get('foolframe.gen.website_title'),
            'title' => $this->preferences->get('foolframe.gen.index_title'),
            'notices' => $this->preferences->get('foolframe.theme.header_text'),
            'media_http' => preg_split("/\\r\\n|\\r|\\n/", $this->preferences->get('foolfuuka.boards.media_balancers')),
            'media_https' => preg_split("/\\r\\n|\\r|\\n/", $this->preferences->get('foolfuuka.boards.media_balancers_https')),
            'global_search_enabled' => (bool)$this->preferences->get('foolfuuka.sphinx.global')
        ];
        if ($this->getQuery('theme')) {
            foreach (['bootstrap.legacy.css', 'font-awesome/css/font-awesome.css', 'style.css', 'flags.css',
                         'jquery.js', 'bootstrap.min.js', 'board.js', 'plugins.js'] as $asset) {
                $res['site']['assets'][] = $this->theme->getAssetManager()->getAssetLink($asset);
            }
        }

        $radices = [];
        switch ($mode) {
            case 'boards':
                $radices = $this->radix_coll->getBoards();
                break;
            case 'archives':
                $radices = $this->radix_coll->getArchives();
                break;
            default:
                $radices = $this->radix_coll->getAll();
                break;
        }

        foreach ($radices as $board) {
            $res[($board->archive ? 'archives' : 'boards')][$board->id] = [
                'name' => $board->name,
                'shortname' => $board->shortname,
                'board_url' => $this->uri->create($board->shortname . '/'),
                'threads_per_page' => $board->getValue('threads_per_page'),
                'original_board_url' => ($board->archive ? $board->getValue('board_url') : $this->uri->create($board->shortname . '/')),
                'thumbs_url' => $board->getValue('thumbs_url'),
                'images_url' => $board->getValue('images_url'),
                'anonymous_default_name' => $board->getValue('anonymous_default_name'),
                'max_comment_characters_allowed' => $board->getValue('max_comment_characters_allowed'),
                'max_comment_lines_allowed' => $board->getValue('max_comment_lines_allowed'),
                'cooldown_new_comment' => $board->getValue('cooldown_new_comment'),
                'transparent_spoiler' => (bool)$board->getValue('transparent_spoiler'),
                'enable_flags' => (bool)$board->getValue('enable_flags'),
                'display_exif' => (bool)$board->getValue('display_exif'),
                'enable_poster_hash' => (bool)$board->getValue('enable_poster_hash'),
                'disable_ghost' => (bool)$board->getValue('disable_ghost'),
                'is_nsfw' => (bool)$board->getValue('is_nsfw'),
                'hide_thumbnails' => (bool)$board->getValue('hide_thumbnails'),
                'search_enabled' => (bool)$board->getValue('sphinx'),
                'board_hidden' => (bool)$board->getValue('hidden')
            ];
            if (!$board->archive) {
                $res[($board->archive ? 'archives' : 'boards')][$board->id]['internal_board_settings'] = [
                    'op_image_upload_necessity' => $board->getValue('op_image_upload_necessity'),
                    'thumbnail_op_width' => $board->getValue('thumbnail_op_width'),
                    'thumbnail_op_height' => $board->getValue('thumbnail_op_height'),
                    'thumbnail_reply_width' => $board->getValue('thumbnail_reply_width'),
                    'thumbnail_reply_height' => $board->getValue('thumbnail_reply_height'),
                    'max_image_size_kilobytes' => $board->getValue('max_image_size_kilobytes'),
                    'max_image_size_width' => $board->getValue('max_image_size_width'),
                    'max_image_size_height' => $board->getValue('max_image_size_height'),
                    'max_posts_count' => $board->getValue('max_posts_count'),
                    'max_images_count' => $board->getValue('max_images_count'),
                    'cooldown_new_thread' => $board->getValue('cooldown_new_thread'),
                    'thread_lifetime' => $board->getValue('thread_lifetime'),
                    'min_image_repost_time' => $board->getValue('min_image_repost_time')
                ];
            } else {
                $res[($board->archive ? 'archives' : 'boards')][$board->id]['archive_full_images'] =
                    (bool)$board->getValue('archive_full_images');
            }
        }
        $extra = [];
        $extra = Hook::forge('foolframe.themes.generic.index_nav_elements')->setObject($this)->setParam('nav', $extra)->execute()->get($extra);
        $extra = Hook::forge('foolfuuka.themes.default.index_nav_elements')->setObject($this)->setParam('nav', $extra)->execute()->get($extra);

        foreach ($extra as $item) {
            foreach($item['elements'] as $i) {
                $res[$item['title']][] = [
                    'url' => $i['href'],
                    'text' => $i['text']
                ];
            }
        }

        return $this->response->setData($res);
    }

    public function get_index()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board selected.')])->setStatusCode(422);
        }

        $page = $this->getQuery('page');

        if (!$page) {
            return $this->response->setData(['error' => _i('The "page" parameter is missing.')])->setStatusCode(422);
        }

        if (!ctype_digit((string) $page)) {
            return $this->response->setData(['error' => _i('The value for "page" is invalid.')])->setStatusCode(422);
        }

        $page = intval($page);

        $order = 'by_thread';

        if($this->getQuery('order') !== null && in_array($this->getQuery('order'), ['by_post', 'by_thread', 'ghost'])) {
            $order = $this->getQuery('order');
        }

        try {
            $options = [
                'per_page' => $this->radix->getValue('threads_per_page'),
                'per_thread' => 5,
                'order' => $order
            ];

            $board = Board::forge($this->getContext())
                ->getLatest()
                ->setRadix($this->radix)
                ->setPage($page)
                ->setOptions($options);

            foreach ($board->getCommentsUnsorted() as $comment) {
                $this->apify($comment);
            }

            $this->response->setData($board->getComments());
        } catch (\Foolz\FoolFuuka\Model\BoardThreadNotFoundException $e) {
            return $this->response->setData(['error' => _i('Thread not found.')]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => _i('Encountered an unknown error.')])->setStatusCode(500);
        }

        return $this->response;
    }

    public function get_gallery()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board selected.')])->setStatusCode(422);
        }

        $page = $this->getQuery('page');

        if (!$page) {
            return $this->response->setData(['error' => _i('The "page" parameter is missing.')])->setStatusCode(422);
        }

        if (!ctype_digit((string)$page)) {
            return $this->response->setData(['error' => _i('The value for "page" is invalid.')])->setStatusCode(422);
        }

        $page = intval($page);

        try {
            $board = Board::forge($this->getContext())
                ->getThreads()
                ->setRadix($this->radix)
                ->setPage($page)
                ->setOptions('per_page', 100);

            foreach ($board->getCommentsUnsorted() as $comment) {
                $this->apify($comment);
            }

            $this->response->setData($board->getComments());
        } catch (\Foolz\FoolFuuka\Model\BoardThreadNotFoundException $e) {
            return $this->response->setData(['error' => _i('Thread not found.')]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => _i('Encountered an unknown error.')])->setStatusCode(500);
        }

        return $this->response;
    }

    public function get_search()
    {
        // check all allowed search modifiers and apply only these
        $modifiers = [
            'boards', 'tnum', 'subject', 'text', 'username', 'tripcode', 'email', 'filename', 'capcode', 'uid', 'country',
            'image', 'deleted', 'ghost', 'type', 'filter', 'start', 'end', 'results', 'order', 'page'
        ];

        if ($this->getAuth()->hasAccess('comment.see_ip')) {
            $modifiers[] = 'poster_ip';
        }

        $search = [];

        foreach ($modifiers as $modifier) {
            $search[$modifier] = $this->getQuery($modifier, null);
        }

        foreach ($search as $key => $value) {
            if (in_array($key, $modifiers) && $value !== null) {
                if (trim($value) !== '') {
                    $search[$key] = rawurldecode(trim($value));
                } else {
                    $search[$key] = null;
                }
            }
        }

        if ($search['boards'] !== null) {
            $search['boards'] = explode('.', $search['boards']);
        }

        if ($search['image'] !== null) {
            $search['image'] = base64_encode(Media::urlsafe_b64decode($search['image']));
        }

        if ($this->getAuth()->hasAccess('comment.see_ip') && $search['poster_ip'] !== null) {
            if (!filter_var($search['poster_ip'], FILTER_VALIDATE_IP)) {
                return $this->response->setData(['error' => _i('The poster IP you inserted is not a valid IP address.')]);
            }

            $search['poster_ip'] = Inet::ptod($search['poster_ip']);
        }

        if ($search['tnum'] !== null && !is_numeric($search['tnum'])) {
            return $this->response->setData(['error' => _i('Thread number you inserted is not a valid number.')]);
        }

        try {
            $board = Search::forge($this->getContext())
                ->getSearch($search)
                ->setRadix($this->radix)
                ->setPage($search['page'] ? $search['page'] : 1);

            foreach ($board->getCommentsUnsorted() as $comment) {
                $this->apify($comment);
            }

            $comments = $board->getComments();

            $comments['meta'] = [
                'total_found' => (int) $board->getTotalResults(),
                'max_results' => $this->preferences->get('foolfuuka.sphinx.max_matches', 5000),
                'search_title' => $board->title
            ];

            $this->response->setData($comments);
        } catch (\Foolz\FoolFuuka\Model\SearchException $e) {
            return $this->response->setData(['error' => $e->getMessage()]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return $this->response;
    }

    /**
     * Returns a thread
     *
     * Available filters: num (required)
     *
     * @author Woxxy
     */
    public function get_thread()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board selected.')])->setStatusCode(422);
        }

        $num = $this->getQuery('num', null);
        $latest_doc_id = $this->getQuery('latest_doc_id', null);

        if ($num === null) {
            return $this->response->setData(['error' => _i('The "num" parameter is missing.')])->setStatusCode(422);
        }

        if (!ctype_digit((string) $num)) {
            return $this->response->setData(['error' => _i('The value for "num" is invalid.')])->setStatusCode(422);
        }

        $num = intval($num);

        try {
            // build an array if we have more specifications
            if ($latest_doc_id !== null && $latest_doc_id > 0) {
                if (!ctype_digit((string) $latest_doc_id)) {
                    return $this->response->setData(['error' => _i('The value for "latest_doc_id" is malformed.')])->setStatusCode(422);
                }

                $board = Board::forge($this->getContext())
                    ->getThread($num)
                    ->setRadix($this->radix)
                    ->setOptions([
                        'type' => 'from_doc_id',
                        'latest_doc_id' => $latest_doc_id
                    ]);

                foreach ($board->getCommentsUnsorted() as $comment) {
                    $this->apify($comment, ctype_digit((string) $this->getQuery('last_limit')) ? 'last/'.$this->getQuery('last_limit') : 'thread');
                }

                $comments = $board->getComments();

                if (!count($comments)) {
                    $this->response->setData([])->setStatusCode(204);
                } else {
                    $this->response->setData($comments);
                }

            } else {
                $options = [
                    'type' => 'thread',
                ];

                $board = Board::forge($this->getContext())
                    ->getThread($num)
                    ->setRadix($this->radix)
                    ->setOptions($options);

                $thread_status = $board->getThreadStatus();
                $last_modified = $thread_status['last_modified'];

                $this->setLastModified($last_modified);

                if (!$this->response->isNotModified($this->request)) {
                    $bulks = $board->getCommentsUnsorted();
                    foreach ($bulks as $bulk) {
                        $this->apify($bulk, ctype_digit((string) $this->getQuery('last_limit')) ? 'last/'.$this->getQuery('last_limit') : 'thread');
                    }

                    $this->response->setData($board->getComments());
                }
            }
        } catch (\Foolz\FoolFuuka\Model\BoardThreadNotFoundException $e) {
            return $this->response->setData(['error' => _i('Thread not found.')]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => _i('Encountered an unknown error.')])->setStatusCode(500);
        }

        return $this->response;
    }

    public function get_post()
    {
        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(422);
        }

        $num = $this->getQuery('num');

        if (!$num) {
            return $this->response->setData(['error' => _i('The "num" parameter is missing.')])->setStatusCode(422);
        }

        if (!Board::isValidPostNumber($num)) {
            return $this->response->setData(['error' => _i('The value for "num" is invalid.')])->setStatusCode(422);
        }

        try {
            $comment = Board::forge($this->getContext())
                ->getPost($num)
                ->setRadix($this->radix)
                ->getComment();

            $this->apify($comment);
            $this->setLastModified($comment->comment->timestamp_expired ?: $comment->comment->timestamp);

            if (!$this->response->isNotModified($this->request)) {
                $this->response->setData($comment);
            }
        } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
            return $this->response->setData(['error' => _i('Post not found.')]);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return $this->response;
    }

    public function post_user_actions()
    {
        if (!$this->checkCsrfToken()) {
            return $this->response->setData(['error' => _i('The security token was not found. Please try again.')]);
        }

        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(422);
        }

        if ($this->getPost('action') === 'report') {
            try {
                $this->report_coll->add(
                    $this->radix,
                    $this->getPost('doc_id'),
                    $this->getPost('reason'),
                    Inet::ptod($this->getRequest()->getClientIp())
                );
            } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            }

            return $this->response->setData(['success' => _i('You have successfully submitted a report for this post.')]);
        }

        if ($this->getPost('action') === 'delete') {
            try {
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $this->getPost('doc_id'))
                    ->setCommentOptions('clean', false)
                    ->setRadix($this->radix)
                    ->getComment();

                $comment = new Comment($this->getContext(), $comment);
                $comment->delete($this->getPost('password'));
            } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            } catch (\Foolz\FoolFuuka\Model\CommentDeleteWrongPassException $e) {
                return $this->response->setData(['error' => $e->getMessage()]);
            }

            return $this->response->setData(['success' => _i('This post was deleted.')]);
        }
    }

    public function post_mod_actions()
    {
        if (!$this->checkCsrfToken()) {
            return $this->response->setData(['error' => _i('The security token was not found. Please try again.')]);
        }

        if (!$this->getAuth()->hasAccess('comment.mod_capcode')) {
            return $this->response->setData(['error' => _i('Access Denied.')])->setStatusCode(403);
        }

        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(422);
        }

        $function = 'mod_action_' . $this->getPost('action');

        if ($this->getPost('action') !== '' && method_exists($this, $function)) {
            return $this->$function();
        }
        return $this->get_404();
    }

    private function mod_action_delete_report()
    {
        try {
            $this->report_coll->delete($this->getPost('id'));
        } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
        }

        return $this->response->setData(['success' => _i('The report was deleted.')]);
    }

    private function mod_action_delete_post()
    {
        try {
            $comment = Board::forge($this->getContext())
                ->getPost()
                ->setOptions('doc_id', $this->getPost('id'))
                ->setRadix($this->radix)
                ->getComment();

            $comment = new Comment($this->getContext(), $comment);
            $comment->delete();
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
        }

        return $this->response->setData(['success' => _i('This post was deleted.')]);
    }

    private function mod_action_delete_image()
    {
        try {
            $media = $this->media_factory->getByMediaId($this->radix, $this->getPost('id'));
            $media = new Media($this->getContext(), CommentBulk::forge($this->radix, null, $media));
            $media->delete(true, true, true);
        } catch (\Foolz\FoolFuuka\Model\MediaNotFoundException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
        }

        return $this->response->setData(['success' => _i('This image was deleted.')]);
    }

    private function mod_action_ban_image()
    {
        $global = false;
        if ((bool)$this->getPost('global') === true) {
            $global = true;
        }

        try {
            $media = $this->media_factory->getByMediaId($this->radix, $this->getPost('id'));
            $media = new Media($this->getContext(), CommentBulk::forge($this->radix, null, $media));
            $media->ban($global);
        } catch (\Foolz\FoolFuuka\Model\MediaNotFoundException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
        }

        return $this->response->setData(['success' => _i('This image was banned.')]);
    }

    private function mod_action_ban_user()
    {
        try {
            $this->ban_factory->add(Inet::ptod($this->getPost('ip')),
                $this->getPost('reason'),
                $this->getPost('length'),
                $this->getPost('board_ban') === 'global' ? array() : array($this->radix->id)
            );

            if ((bool)$this->getPost('delete_user') === true) {
                if ($this->getPost('board_ban') === 'global') {
                    foreach ($this->radix_coll->getAll() as $new_radix) {
                        $board = Board::forge($this->getContext())
                            ->getPostsByIP()
                            ->setOptions('poster_ip', inet::ptod($this->getPost('ip')))
                            ->setRadix($new_radix);

                        foreach ($board->getCommentsUnsorted() as $comment) {
                            try {
                                $comment = new Comment($this->getContext(), $comment);
                                $comment->delete();
                            } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
                            } // we don't want any errors here, just continue
                        }
                    }
                } else {
                    $board = Board::forge($this->getContext())
                        ->getPostsByIP()
                        ->setOptions('poster_ip', inet::ptod($this->getPost('ip')))
                        ->setRadix($this->radix);

                    foreach ($board->getCommentsUnsorted() as $comment) {
                        try {
                            $comment = new Comment($this->getContext(), $comment);
                            $comment->delete();
                        } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
                        } // we don't want any errors here, just continue
                    }
                }
            }
            if ((bool)$this->getPost('ban_public') === true && $this->getPost('doc_id') !== 'undefined') {
                try {
                    $comment = Board::forge($this->getContext())
                        ->getPost()
                        ->setOptions('doc_id', $this->getPost('doc_id'))
                        ->setRadix($this->radix)
                        ->getComment();

                    $comment = new Comment($this->getContext(), $comment);

                    $new_comment = [
                        'comment' => $comment->comment->comment . "\n\n" . '[banned](USER WAS BANNED FOR THIS POST)[/banned]'
                    ];

                    $comment->commentUpdate($new_comment);
                } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
                } // fine if no post to update
            }
        } catch (\Foolz\FoolFuuka\Model\BanException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
        } catch (\Exception $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }

        return $this->response->setData(['success' => _i('This user was banned.')]);
    }

    private function mod_action_toggle_sticky()
    {
        try {
            $comment = Board::forge($this->getContext())
                ->getPost()
                ->setOptions('doc_id', $this->getPost('id'))
                ->setRadix($this->radix)
                ->getComment();

            $thread = Board::forge($this->getContext())
                ->getThread($comment->comment->thread_num)
                ->setRadix($this->radix)
                ->setOptions(['type' => 'thread'])
                ->getThreadStatus();

            $comment = new Comment($this->getContext(), $comment);
            $comment->setSticky((int)!$thread['sticky']);
        } catch (\Foolz\FoolFuuka\Model\CommentUpdateException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(422);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
        }

        return $this->response->setData(['success' => _i('The sticky status of this post has been updated.')]);
    }

    private function mod_action_toggle_locked()
    {
        try {
            $comment = Board::forge($this->getContext())
                ->getPost()
                ->setOptions('doc_id', $this->getPost('id'))
                ->setRadix($this->radix)
                ->getComment();

            $thread = Board::forge($this->getContext())
                ->getThread($comment->comment->thread_num)
                ->setRadix($this->radix)
                ->setOptions(['type' => 'thread'])
                ->getThreadStatus();

            $comment = new Comment($this->getContext(), $comment);
            $comment->setLocked((int)!$thread['closed']);
        } catch (\Foolz\FoolFuuka\Model\CommentUpdateException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(422);
        } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
        }

        return $this->response->setData(['success' => _i('The locked status of this post has been updated.')]);
    }


    private function mod_action_delete_all_reports()
    {
        $reports = [];

        try {
            if ($this->getPost('ip')) {
                $reports = $this->report_coll->getByReporterIp(Inet::ptod($this->getPost('ip')));
            } else {
                $reports = $this->report_coll->getAll();
            }

            foreach ($reports as $report) {
                $this->report_coll->delete($report->id);
            }
        } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }
        return $this->response->setData(['success' => _i('Successfully removed all reports')]);
    }

    private function mod_action_delete_all_report_posts()
    {
        $reports = [];

        try {
            if ($this->getPost('ip')) {
                $reports = $this->report_coll->getByReporterIp(Inet::ptod($this->getPost('ip')));
            } else {
                $reports = $this->report_coll->getAll();
            }
        } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }

        foreach ($reports as $report) {
            try {
                $this->report_coll->delete($report->id);
                $radix = $this->radix_coll->getById($report->board_id);
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $report->doc_id)
                    ->setRadix($radix)
                    ->getComment();

                $comment = new Comment($this->getContext(), $comment);
                $comment->delete();
            } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
                // in this case we can safely continue
            } catch (\Foolz\FoolFuuka\Model\BoardException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
            } catch (\Exception $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
            }
        }
        return $this->response->setData(['success' => _i('Successfully removed all reported posts')]);
    }

    private function mod_action_delete_all_report_images()
    {
        $reports = [];

        try {
            if ($this->getPost('ip')) {
                $reports = $this->report_coll->getByReporterIp(Inet::ptod($this->getPost('ip')));
            } else {
                $reports = $this->report_coll->getAll();
            }
        } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }

        foreach ($reports as $report) {
            try {
                $radix = $this->radix_coll->getById($report->board_id);
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $report->doc_id)
                    ->setRadix($radix)
                    ->getComment();

                $comment = new Comment($this->getContext(), $comment);
                if ($comment->media === null) {
                    continue;
                }
                $media = $this->media_factory->getByMediaId($radix, $comment->media->media_id);
                $media = new Media($this->getContext(), CommentBulk::forge($radix, null, $media));
                $media->delete(true, true, true);
            } catch (\Foolz\FoolFuuka\Model\MediaNotFoundException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            } catch (\Exception $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
            }
        }
        return $this->response->setData(['success' => _i('Successfully removed all reported images')]);
    }

    private function mod_action_ban_all_report_images()
    {
        $reports = [];

        try {
            if ($this->getPost('ip')) {
                $reports = $this->report_coll->getByReporterIp(Inet::ptod($this->getPost('ip')));
            } else {
                $reports = $this->report_coll->getAll();
            }
        } catch (\Foolz\FoolFuuka\Model\ReportException $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        }

        $global = false;
        if ((bool)$this->getPost('global') === true) {
            $global = true;
        }

        foreach ($reports as $report) {
            try {
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $report->doc_id)
                    ->setRadix($report->radix)
                    ->getComment();

                $comment = new Comment($this->getContext(), $comment);
                if ($comment->media === null) {
                    continue;
                }
                $media = $this->media_factory->getByMediaId($this->radix, $comment->media->media_id);
                $media = new Media($this->getContext(), CommentBulk::forge($report->radix, null, $media));
                $media->ban($global);
            } catch (\Foolz\FoolFuuka\Model\MediaNotFoundException $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(404);
            } catch (\Exception $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
            }
        }
        return $this->response->setData(['success' => _i('Successfully banned all reported images')]);
    }

    private function mod_action_delete_user()
    {
        if (!$this->getPost('ip') || !filter_var($this->getPost('ip'), FILTER_VALIDATE_IP)) {
            // This function is potentially destructive, so we must be sure we have valid IP
            return $this->response->setData(['error' => _i('No valid IP address given.')]);
        }
        try {
            $board = Board::forge($this->getContext())
                ->getPostsByIP()
                ->setOptions('poster_ip', inet::ptod($this->getPost('ip')))
                ->setRadix($this->radix);

            foreach ($board->getCommentsUnsorted() as $comment) {
                try {
                    $comment = new Comment($this->getContext(), $comment);
                    $comment->delete();
                } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
                } // we don't want any errors here, just continue
            }
        } catch (\Exception $e) {
            return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
        } // on first real error we need to discontinue
        return $this->response->setData(['success' => _i('Successfully removed all posts by IP.')]);
    }


    public function post_edit_post()
    {
        if (!$this->checkCsrfToken()) {
            return $this->response->setData(['error' => _i('The security token was not found. Please try again.')]);
        }

        if (!$this->getAuth()->hasAccess('comment.mod_capcode')) {
            return $this->response->setData(['error' => _i('Access Denied.')])->setStatusCode(403);
        }

        if (!$this->check_board()) {
            return $this->response->setData(['error' => _i('No board was selected.')])->setStatusCode(422);
        }

        if ($this->getPost('action') === 'edit_post') {
            try {
                $comment = Board::forge($this->getContext())
                    ->getPost()
                    ->setOptions('doc_id', $this->getPost('doc_id'))
                    ->setRadix($this->radix)
                    ->getComment();

                $new_comment = [
                    'title' => $this->getPost('subject'),
                    'name' => $this->getPost('name'),
                    'trip' => $this->getPost('trip'),
                    'email' => $this->getPost('email'),
                    'poster_country' => $this->getPost('poster_country'),
                    'poster_hash' => $this->getPost('poster_hash'),
                    'capcode' => $this->getPost('capcode'),
                    'comment' => $this->getPost('comment')
                ];

                if ($this->getPost('media_edit') == 'true') {
                    $new_comment['media_filename'] = $this->getPost('filename');
                    $new_comment['media_w'] = $this->getPost('media_w');
                    $new_comment['media_h'] = $this->getPost('media_h');
                    $new_comment['preview_w'] = $this->getPost('preview_w');
                    $new_comment['preview_h'] = $this->getPost('preview_h');
                    $new_comment['spoiler'] = $this->getPost('spoiler');
                }

                if ($this->getPost('transparency') == 'true') {
                    $new_comment['comment'] .= "\n\n" . '[info]This post was modified by ' .
                        $this->preferences->get('foolframe.gen.website_title') . " " .
                        $this->config->get('foolz/foolframe', 'foolauth', 'groups')[$this->getAuth()->getUser()->getGroupId()]['name'] .
                        ' on ' . date("Y-m-d") . '[/info]';
                }

                // might want to do some validation here or in model

                $comment = new Comment($this->getContext(), $comment);
                $comment->commentUpdate($new_comment);
            } catch (\Foolz\FoolFuuka\Model\BoardPostNotFoundException $e) {
                return $this->response->setData(['error' => _i('Post not found.')]);
            } catch (\Exception $e) {
                return $this->response->setData(['error' => $e->getMessage()])->setStatusCode(500);
            }
            return $this->response->setData(['success' => _i('Successfully edited comment')]);
        }
    }
}
