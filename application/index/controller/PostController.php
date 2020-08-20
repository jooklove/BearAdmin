<?php

namespace app\index\controller;

use app\index\model\Classify;
use app\index\model\Comments;
use app\index\model\Label;
use app\index\model\LabelPost;
use app\index\model\LabelUser;
use app\index\model\PostLike;
use app\index\model\Posts;
use app\index\model\PostTeam;
use app\index\traits\Wechat;
use app\index\validate\PostsValidate;
use think\Exception;
use think\exception\PDOException;
use think\facade\Request;

class PostController extends Controller
{
    use Wechat;

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
        $cid = Request::param('cid') ?? 1;//debug
        $classify = Classify::get($cid);
        if (empty($classify))
            $this->error('不存在的分类');

        $label = Label::getLabel(Label::MAJOR);

        $this->assign(['label'=>$label, 'cid'=>$cid]);

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
        $lid = Request::param('lid');
        $title = Request::param('title');
        $content = Request::param('content');
        $images = Request::param('images');

        if (empty($cid))
            $this->error('分类不能为空');

        //查询用户单位id
        $where = [
            ['uid' => $this->uid],
            ['lid_type' => Label::UNIT],
        ];
        $unit_lid = LabelUser::where($where)->value('lid');

        $post = [
            'uid' => $this->user['id'],
            'cid' => $cid,
            'title' => $title,
            'content' => $content,
            'unit_lid' => $unit_lid,
            'major_lid' => $lid,
        ];

        $validate = new PostsValidate();
        if ($validate->check($post)) {
            $postid = Posts::create($post)->getLastInsID();
        } else {
            $this->error($validate->getError());
        }

        $label_post = [];
        $label_post[0]['postid'] = $postid;
        $label_post[0]['lid'] = $unit_lid;
        $label_post[0]['lid_type'] = Label::UNIT;
        $label_post[1]['postid'] = $postid;
        $label_post[1]['lid'] = $lid;
        $label_post[1]['lid_type'] = Label::MAJOR;

        LabelPost::create($label_post);

        $tempmsg = [
            'username' => $this->user['username'],
            'mobile' => $this->user['mobile'],
            'title' => $title,
        ];
        //发送模板消息通知审核员
        $this->senTempMsg($tempmsg, $unit_lid);

        return $this->_result('等待管理员审核后您的帖子才可以显示');
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
            return $this->_result('用户不存在',1);

        $id = Request::param('id');
        $post = Posts::get($id);
        if ($post['uid'] != $uid)
            return $this->_result('无删除权限',1);

        $where = [
            ['id','=', $id],
            ['uid','=', $uid],
        ];
        $res = Posts::where($where)->delete();
        if ($res)
            return $this->_result('删除成功');
        else
            return $this->_result('删除失败',1);
    }

    public function comment()
    {
        $pid = Request::param('pid'); //回复的评论id
        $postid = Request::param('postid');
        $post = Posts::get($postid);
        if (empty($post)) {
            return $this->_result('不存在的帖子',1);
        }

        $content = Request::param('content');
        if (mb_strlen($content,'utf8') > 140)
            return $this->_result('评论内容不能超过140个字',1);

        $img = Request::param('img');
        if (!empty($img))
            $img = json_encode($img);

        $data = [
            'pid' => $pid,
            'postid' => $postid,
            'uid' => $this->uid,
            'content' => $content,
            'img' => $img,
        ];

        if (Comments::create($data)) {
            return $this->_result('评论成功');
        } else {
            return $this->_result('评论失败',1);
        }
    }

    public function like()
    {
        return $this->likeOrTeam(Request::action(true));
    }

    public function team()
    {
        return $this->likeOrTeam(Request::action(true));
    }

    public function likeOrTeam($type='like')
    {
        $postid = Request::param('postid') ?? 1;
        $post = Posts::get($postid);
        if (empty($post)) {
            return $this->_result('不存在的帖子',1);
        }
        if ($type=='like') {
            $model = new PostLike();
            $field = 'likes';
            $join = '点赞';
            $cancel = '取消点赞';
        } else {
            $model = new PostTeam();
            $field = 'team_num';
            $join = '加入战队';
            $cancel = '退出战队';
        }
        $id = $model->where('uid',$this->uid)->where('postid',$postid)->value('id');
        if (empty($id)) {
            $data = [
                'uid' => $this->uid,
                'postid' => $postid
            ];
            if ($model->save($data)) {
                $re = Posts::where('id', $postid)->setInc($field);
                return $this->_result($join.'成功');
            } else {
                return $this->_result($join.'失败',1);
            }
        } else {
            $res = $model->where('id', $id)->delete();
            if ($res) {
                Posts::where('id', $postid)->where($field,'>',0)->setDec($field);
                return $this->_result($cancel.'成功');
            } else {
                return $this->_result($cancel.'失败',1);
            }
        }
    }
}
