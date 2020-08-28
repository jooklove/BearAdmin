<?php

namespace app\index\controller;

use app\common\model\User;
use app\index\model\Attachment;
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
use think\Db;
use think\facade\Request;

class PostController extends Controller
{
//    use Wechat;

    //无需验证登录的方法
    protected $authExcept = ['index'];

    public $classify = [1=>'我有金点子',2=>'我有疑难杂症'];

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
        $classify = Classify::get($cid);
        if (empty($classify))
            $this->error('请选择发帖分类');

        $label = Label::getLabel(Label::MAJOR,false);

        $this->assign(['label'=>$label, 'cid'=>$cid]);

        return $this->fetch();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Posts $posts)
    {
        $cid = Request::param('cid');
        $major_lid = Request::param('major_lid');
        $title = Request::param('title');
        $content = Request::param('content');
        $images = Request::param('images');
        $attach_id = Request::param('attach_id');

        if (empty($cid))
            $this->_result('分类不能为空',1);
        if (!empty($images))
            $images = json_encode($images);

        //用户单位id
        $unit_lid = $this->user['unit_lid'];

        $post = [
            'uid' => $this->uid,
            'cid' => $cid,
            'title' => $title,
            'images' => $images,
            'content' => $content,
            'unit_lid' => $unit_lid,
            'major_lid' => $major_lid,
            'attach_id' => $attach_id,
            'added_on' => time(),
        ];

        $validate = new PostsValidate();
        if ($validate->check($post)) {
            $postid = $posts->insertGetId($post);
        } else {
//            $this->error($validate->getError());
            return $this->_result($validate->getError(),1);
        }

        $label_post = [];
        $label_post[0]['postid'] = $postid;
        $label_post[0]['lid'] = $unit_lid;
        $label_post[0]['lid_type'] = Label::UNIT;
        $label_post[1]['postid'] = $postid;
        $label_post[1]['lid'] = $major_lid;
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
    public function read($id,Posts $post_m)
    {
        if (empty($id))
            $this->error('帖子不存在');

        $post = $post_m::get($id);
        if (empty($post))
            $this->error('帖子不存在');
//        print_r($post);die();
        //增加帖子浏览量
        Posts::where('id',$id)->setInc('browse');

        $post['images'] = $post['images'] ?json_decode($post['images'],true):[];
        $post['comment_num'] = Comments::where('postid',$id)->count();//评论数
        $post['is_like'] = PostLike::where('postid',$id)
                            ->where('uid',$this->uid)->count();//当前用户是否点赞
        $post['is_team'] = PostTeam::where('postid',$id)
            ->where('uid',$this->uid)->count();//当前用户是否点赞

        $user     = User::get($post['uid']); //发帖的用户信息
        $comment  = $post_m->getComment($id,$this->uid);//评论信息
        $likeComment = Db::table('league_comment_like')
            ->where('uid',$this->uid)->where('postid',$id)->column('comment_id');
        $postTeam = $post_m->getPostTeam($id);//帖子战队成员列表
        $like     = $post_m->getPostLike($id);//帖子点赞用户列表
        $label    = Label::getLabel();//标签列表
        $post['attach_url'] = Attachment::where('id',$post['attach_id'])->value('url');
//        dump($comment);die();
        $this->assign([
            'post' => $post,
            'user' => $user,
            'like' => $like,
            'comment' => $comment,
            'postTeam' => $postTeam,
            'label' => $label,
        ]);

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
     * @param Posts $posts
     * @return \think\Response
     */
    public function delete()
    {
        $uid = $this->uid;
        if (empty($uid))
            return $this->_result('用户不存在',1);

        $id = Request::param('id');
        $post = Posts::get($id);
        if ($post['uid'] != $uid)
            return $this->_result('无删除权限',1);
        //软删除
        return $post->delete() ?$this->_result('删除成功'):$this->_result('删除失败',1);
    }

    public function comment($postid,$reply_uid=0,$pid=0)
    {
        $this->assign('postid',$postid);
        $this->assign('reply_uid',$reply_uid);
        $this->assign('pid',$pid);

        $title = Posts::where('id',$postid)->value('title');
        $reply_nickname = '已注销用户';
        if ($reply_uid) {
            $reply_nickname = User::where('id',$reply_uid)->value('nickname');
        }

        $this->assign('title',$title);
        $this->assign('reply_nickname',$reply_nickname);

        return $this->fetch();
    }

    public function saveComment()
    {
        $pid = Request::param('pid'); //回复的评论id
        $postid = Request::param('postid');
        $reply_uid = Request::param('reply_uid');
        $post = Posts::get($postid);
        if (empty($post)) {
            return $this->_result('帖子不存在',1);
        }

        $content = Request::param('content');
//        if (mb_strlen($content,'utf8') > 140)
//            return $this->_result('评论内容不能超过140个字',1);

        $img = Request::param('img');
        if (!empty($img))
            $img = json_encode($img);

        $data = [
            'pid' => $pid,
            'postid' => $postid,
            'uid' => $this->uid,
            'reply_uid' => $reply_uid,
            'content' => $content,
            'img' => $img,
        ];

        if (Comments::create($data)) {
            return $this->_result('评论成功');
        } else {
            return $this->_result('评论失败',1);
        }
    }
    //点赞评论
    public function likeComment()
    {
        $id = Request::param('id');
        $model = Db::table('league_comment_like');
        $exist_id = $model->where('uid',$this->uid)->where('comment_id',$id)->value('comment_id');
        if ($exist_id){
            Comments::where('id',$id)->setDec('likes');
            $res = $model->where('comment_id',$id)->delete();
            return $res ?$this->_result('取消点赞') : $this->_result('操作失败',1);
        } else {
            Comments::where('id',$id)->setInc('likes');
            $res = $model->insert(['comment_id'=>$id,'uid'=>$this->uid,'create_time'=>time()]);
            return $res ?$this->_result('点赞成功') : $this->_result('操作失败',1);
        }
    }
    //删除评论
    public function del_comment($id)
    {
        $res = Comments::where('id',$id)->where('uid',$this->uid)->delete();
        return $res ? $this->_result('删除成功') : $this->_result('删除失败',1);
    }
    //点赞贴子
    public function like()
    {
        return $this->likeOrTeam(Request::action(true));
    }
    //加入战队贴子
    public function team()
    {
        return $this->likeOrTeam(Request::action(true));
    }

    public function likeOrTeam($type='like')
    {
        $postid = Request::param('id');
        $post = Posts::get($postid);
        if (empty($post)) {
            return $this->_result('帖子不存在',1);
        }
        if ($type=='like') {
            $model = new PostLike();
            $field = 'likes';
            $join = '点赞';
            $cancel = '取消点赞';
        } else {
            if ($post['uid']==$this->uid)
                return $this->_result('不能加入自己的帖子',1);
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
