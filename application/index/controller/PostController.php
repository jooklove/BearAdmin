<?php

namespace app\index\controller;

use app\index\model\LabelPost;
use app\index\model\LabelUser;
use app\index\model\Posts;
use app\index\validate\PostsValidate;
use think\facade\Request;

class PostController extends Controller
{
    //无需验证登录的方法
    protected $authExcept = [
        'index', 'read'
    ];

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        //
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $cid = Request::param('cid');
        $this->assign('cid', $cid);
        return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $cid = Request::param('cid');
        $title = Request::param('title');
        $content = Request::param('content');
        $images = Request::param('images');

        if (empty($cid))
            $this->error('分类不能为空');

        $post = [
            'uid' => $this->user['id'],
            'cid' => $cid,
            'title' => $title,
            'content' => $content,
            'images' => $images,
        ];

        $validate = new PostsValidate();
        if ($validate->check($post)) {
            $postid = Posts::create($post)->getLastInsID();
        } else {
            $this->error($validate->getError());
        }

        $lid = LabelUser::where('uid',$this->uid)->field('lid')->select();
        $label_post = [];
        foreach ($lid as $key=>$id) {
            $label_post[$key]['postid'] = $postid;
            $label_post[$key]['lid'] = $id;
        }
        LabelPost::create($label_post);
        return $this->_result('发帖成功');
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read()
    {
        $id = Request::param('id');
        if (empty($id))
            $this->_result('帖子不存在',1);

        $post = Posts::get($id);
        if (empty($post))
            $this->_result('帖子不存在',1);

        $user = $post->user;
        $comment = $post->comment;
        $postTeam = $post->postTeam;
        $this->assign(compact($post,$user,$comment,$postTeam));
        return $this->fetch();
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        return $this->fetch();
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * 删除指定资源
     *
     * @return \think\Response
     */
    public function delete()
    {
        $uid = $this->user['id'];
        if (empty($uid))
            return $this->_result('用户不存在');
        $id = Request::param('id');
        $where = [
            ['id','=', $id],
            ['uid','=', $uid],
        ];
        $res = Posts::where($where)->delete();
        if ($res)
            return $this->_result('删除成功');
        else
            return $this->_result('删除失败');
    }
}
