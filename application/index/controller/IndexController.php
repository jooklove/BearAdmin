<?php

namespace app\index\controller;


use app\index\model\Label;
use app\index\model\LabelPost;
use app\index\model\Posts;
use app\index\model\PostTop;
use think\Db;
use think\facade\Request;

class IndexController extends Controller
{
    //无需验证登录的方法
    protected $authExcept = [
        'index','home'
    ];

    //论坛首页
    public function home()
    {
        $type = Request::param('type');
        if ($type == 'labelPost') {
            $this->labelPost();
        } elseif ($type == 'hotPost') {
            $this->hotPost();
        } elseif ($type == 'activePost') {
            $this->activePost();
        } else {
            $this->allPost();
        }

        //获取标签
        $label = Label::getLabel();

        $this->assign(compact($label));

        return $this->fetch();
    }

    //首页-全部帖子
    private function allPost()
    {
        //存放置顶和热门贴的id
        $topAndHot = [];
        //获取置顶贴子
        $topPost = Posts::getTopPost();
        if ($topPost)
            $topAndHot[] = $topPost['id'];

        //获取前三条热门帖子
        $hotPost = Posts::hot($topPost['id']);
        if (!empty($hotPost)) {
            foreach ($hotPost as &$hp) {
                $topAndHot[] = $hp['id'];
                $hp['is_hot'] = 1;
            }
        }
        //获取排除置顶和热门的贴子
        $post = (array) Posts::where('id','not in',$topAndHot)->paginate(10);

        $this->assign([
            'post' => $post,
            'topPost' => $topPost,
            'hotPost' => $hotPost,
        ]);
    }

    //首页-热门帖子
    private function hotPost()
    {
        $post = Posts::alias('p')->order('browse','desc')->paginate(10);

        $this->assign(compact($post));
    }

    //首页-活跃（最新回复）帖子
    private function activePost()
    {
        $post = Posts::alias('p')
                ->leftJoin('comments c','p.id = c.postid')
                ->field('p.*')
                ->order('c.added_on','desc')
                ->paginate(10);

        $this->assign(compact($post));
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
                ->paginate(10);

        $this->assign(compact($post));
    }

    //前台模块首页
    public function index()
    {
        $is_login      = 0;
        $is_login_text = '未登录';
        if ($this->isLogin()) {
            $is_login      = 1;
            $is_login_text = '已登录';
        }

        $this->assign([
            'is_login'      => $is_login,
            'is_login_text' => $is_login_text,
        ]);

        return $this->fetch();
    }
}