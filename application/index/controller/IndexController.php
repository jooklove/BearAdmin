<?php

namespace app\index\controller;


use app\common\model\User;
use app\index\model\Label;
use app\index\model\LabelPost;
use app\index\model\Posts;
use think\facade\Request;
use think\facade\Session;

class IndexController extends Controller
{
    //无需验证登录的方法
    protected $authExcept = [
        'index','home'
    ];

    private $return_data = [];

    //前台模块首页
    public function index(Posts $model)
    {
//        $is_login      = 0;
//        $is_login_text = '未登录';
//        if ($this->isLogin()) {
//            $is_login      = 1;
//            $is_login_text = '已登录';
//        }
//
//        $this->assign([
//            'is_login'      => $is_login,
//            'is_login_text' => $is_login_text,
//        ]);
//
//        return $this->fetch();
        return $this->home($model);
    }

    //论坛首页
    public function home(Posts $model)
    {
        $type = Request::param('type');
        if ($type == 'labelPost') {
            $this->labelPost();
        } elseif ($type == 'hotPost') {
            $this->hotPost();
        } elseif ($type == 'activePost') {
            $this->activePost();
        } else {
            $this->allPost($model);
        }

        //获取标签
        $label = Label::getLabel();

        if ($this->request->isAjax()) {
            $this->return_data['page'] = $this->request->param('page');
            return $this->_result($this->return_data);
        } else {
            $user = $this->user;
            if (empty($user)) {
                $openid = Session::get(self::$openid);
                if (!empty($openid))
                    $user = User::where('openid',$openid)->find();
            }

            $this->assign('label',$label);
            $this->assign('user',$user);
            return $this->fetch('home');
        }
    }

    private function allPost(Posts $model)
    {
        //搜索
        $keyword = $this->request->param('keyword');
        if (!empty($keyword))
            $model = $model->where('title','like','%'.$keyword.'%');
        //获取帖子
        $post = $model->order('sort desc,browse desc')->paginate(10)->toArray();
        $data = $post['data'];
        $page = $this->request->param('page');
        if (empty($page) || $page == 1) {
            $hotNum = 3;
            foreach ($data as $key=>&$val) {
                if ($key==0 && $val['sort']==1) {
                    $val['is_top'] = 1;
                } elseif ($hotNum>0) {
                    $val['is_hot'] = 1;
                    $hotNum--;
                }
            }
        }

        $this->return_data['post'] = $data;
    }

    //首页-热门帖子
    private function hotPost()
    {
        $post = Posts::alias('p')->order('browse','desc')->paginate(10)->toArray();
        $data = $post['data'];
        $this->return_data['post'] = $data;
//        $this->assign(compact($post));
    }

    //首页-活跃（最新回复）帖子
    private function activePost()
    {
        $post = Posts::alias('p')
                ->leftJoin('comments c','p.id = c.postid')
                ->field('p.*')
                ->order('c.added_on','desc')
                ->distinct('c.postid')
                ->paginate(10)->toArray();
        $data = $post['data'];
//        $this->assign(compact($post));
        $this->return_data['post'] = $data;
    }

    //首页-单位/专业标签帖子
    private function labelPost()
    {
        $lid = Request::param('lid');
        $label = Label::get($lid);
        if (empty($label))
            $this->error('标签不存在');
        $postid = LabelPost::where('lid',$lid)->column('postid');
        $post = Posts::alias('p')
                ->leftJoin('label_post l', 'p.id = l.postid')
                ->where('p.id','in',$postid)
                ->where('l.lid_type=\'' .$label['type']. '\'')
                ->order('p.id','desc')
                ->paginate(10)->toArray();
        $data = $post['data'];
//        $this->assign(compact($post));
        $this->return_data['post'] = $data;
    }
}