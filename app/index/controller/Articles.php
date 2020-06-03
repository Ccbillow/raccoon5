<?php


namespace app\index\controller;

use app\model\Article;
use think\db\exception\ModelNotFoundException;
use think\facade\App;
use think\facade\Db;
use think\facade\View;

class Articles extends Base
{
    protected $articleService;
    protected $bookService;
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $this->articleService = app('articleService');
        $this->bookService = app('bookService');
    }

    public function index()
    {
        $id = input('id');
        $article = cache('article:' . $id);
        if ($article == false) {
            try {
                if ($this->end_point == 'id') {
                    $article = Article::with('book')->findOrFail($id);
                    $article['book']['param'] = $article['book']['id'];
                } else {
                    $article = Article::with('book')->where('unique_id', '=', $id)->findOrFail();
                    $article['book']['param'] = $article['book']['unique_id'];
                }
            } catch (ModelNotFoundException $e) {
                abort(404, $e->getMessage());
            }
        }
        $content = file_get_contents(App::getRootPath() . 'public/' . $article->content_url);
        $articles = Article::order('id', 'desc')->limit(10)->cache('topArticles')->select();
        foreach ($articles as &$val) {
            if ($this->end_point == 'id') {
                $val['param'] = $val['id'];
            } else {
                $val['param'] = $val['unique_id'];
            }
        }

        $updates = cache('updateBooks');
        if (!$updates) {
            $updates = $this->bookService->getBooks($this->end_point, 'last_time', [], 20);
            cache('updateBooks', $updates, null, 'redis');
        }


        View::assign([
            'article' => $article,
            'content' => $content,
            'articles' => $articles,
            'books' => $updates
        ]);
        return view($this->tpl);
    }

    public function list()
    {
        $data = $this->articleService->getPaged(config('page.booklist_page'), $this->end_point);
        unset($data['page']['query']['page']);
        $param = '';
        foreach ($data['page']['query'] as $k => $v) {
            $param .= '&' . $k . '=' . $v;
        }

        $updates = cache('updateBooks');
        if (!$updates) {
            $updates = $this->bookService->getBooks($this->end_point, 'last_time', [], 20);
            cache('updateBooks', $updates, null, 'redis');
        }

        View::assign([
            'articles' => $data['articles'],
            'books' => $updates,
            'page' => $data['page'],
            'param' => $param
        ]);
        return view($this->tpl);
    }
}