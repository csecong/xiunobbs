<?php

/*
 * Copyright (C) xiuno.com
 */

!defined('FRAMEWORK_PATH') && exit('FRAMEWORK_PATH not defined.');

include BBS_PATH.'control/common_control.class.php';

class thread_control extends common_control {
	
	function __construct(&$conf) {
		parent::__construct($conf);
		$this->_checked['bbs'] = ' class="checked"';
	}
	
	// 列表
	public function on_index() {
		
		// hook thread_index_before.php
		
		$fid = intval(core::gpc('fid'));
		$tid = intval(core::gpc('tid'));
		$uid = $this->_user['uid'];
		$page = misc::page();
		$thread = $this->thread->read($fid, $tid);
		$this->check_thread_exists($thread);
		$fpage = intval(core::gpc($this->conf['cookie_pre'].'page', 'C'));
		
		// 板块权限检查
		$forum = $this->mcache->read('forum', $fid);
		$this->check_forum_exists($forum);
		$this->check_forum_status($forum);
		$this->check_access($forum, 'read');
		
		$this->thread->format($thread, $forum);
		
		// SEO 优化
		$this->_title[] = $thread['subject'];
		$this->_title[] = $forum['name'];
		
		// hook thread_index_fetch_before.php
		
		// 只缓存了 第一页20个pid，超出则查询 db
		$totalpage = ceil($thread['posts'] / $this->conf['pagesize']);
		$page > $totalpage && $page = $totalpage;
		$postlist = $this->post->index_fetch(array('fid'=>$fid, 'tid'=>$tid, 'page'=>$page), array(), 0, $this->conf['pagesize']);
		
		$postlist = array_filter($postlist);
		
		// php order by pid，一般情况下不用排序，但是偶尔数据库返回的数据为乱序。这里排序有问题！
		//ksort($postlist);	// key 为字符串，排序不稳定。 fid-2-pid-999 fid-2-pid-1000 这种情况
		misc::arrlist_multisort($postlist, 'pid', TRUE);
		
		// 附件，用户
		$uids = $uid ? array($uid) : array();
		$i = ($page - 1) * $this->conf['pagesize'] + 1;
		$reply_pids = $reply_replies = '';
		foreach($postlist as &$post) {
			$this->post->format($post);
			if($post['attachnum'] > 0) {
				$post['attachlist'] = $this->attach->get_list_by_fid_pid($fid, $post['pid'], 0);
			}
			$post['floor'] = $i++;
			$uids[] = $post['uid'];
			
			// 回复的总页数
			$post['totalpage'] = $post['replies'] > 0 ? ceil($post['replies'] / $this->conf['pagesize']) : 0;
			
			if($post['replies'] > 0) {
				$reply_pids .= $post['pid'].'_';
				$reply_replies .= $post['replies'].'_';
				$replypages = ceil($post['replies'] / $this->conf['pagesize']);
				$post['replypages'] = $post['replies'] > $this->conf['pagesize'] ? misc::pages("?reply-list-fid-$post[fid]-pid-$post[pid]-ajax-1.htm", $post['replies'], $replypages, $this->conf['pagesize']) : '';
			}
			
			if($post['rates'] > 0) {
				$post['ratelist'] = $this->rate->get_list_by_fid_pid($fid, $post['pid']);
			}
			
		}
		$reply_pids = substr($reply_pids, 0, -1);
		$reply_replies = substr($reply_replies, 0, -1);
		$this->view->assign('reply_pids', $reply_pids);
		$this->view->assign('reply_replies', $reply_replies);
		
		$uids = array_unique($uids);
		$userlist = $this->user->mget($uids);
		foreach($userlist as &$user) {
			$this->user->format($user);
			$userlist[$user['uid']] = $user;
		}
		$uid && $this->_user = $userlist[$uid];
		$this->view->assign('userlist', $userlist);
		
		// 判断权限
		foreach($postlist as &$post) {
			if($userlist[$post['uid']]['groupid'] == 7) $post['message'] = '<span class="grey">用户被禁言，帖子被屏蔽。</span>';
		}
		
		// 版主管理日志，包含评分列表
		if($thread['modnum'] > 0) {
			$modlist = $this->modlog->get_list_by_fid_tid($fid, $tid);
			foreach($modlist as &$modlog) {
				$this->modlog->format($modlog);
				$modlog['user'] = $this->user->read($modlog['uid']);
				$this->user->format($modlog['user']);	
			}
			$this->view->assign('modlist', $modlist);
		}
		
		// 分页
		$pages = misc::pages("?thread-index-fid-$fid-tid-$tid.htm", $thread['posts'], $page, $this->conf['pagesize']);
		
		// 点击数服务器 seo notfollow
		$click_server = $this->conf['click_server']."?db=tid&w=$tid&r=$tid";
		$scrollbottom = core::gpc('scrollbottom');
		
		// 版主
		$ismod = $this->is_mod($forum, $this->_user);
		
		// userlist
		
		$this->view->assign('click_server', $click_server);
		$this->view->assign('scrollbottom', $scrollbottom);
		$this->view->assign('fid', $fid);
		$this->view->assign('tid', $tid);
		$this->view->assign('page', $page);
		$this->view->assign('fpage', $fpage);
		$this->view->assign('pages', $pages);
		$this->view->assign('thread', $thread);
		$this->view->assign('forum', $forum);
		$this->view->assign('postlist', $postlist);
		$this->view->assign('ismod', $ismod);
		// hook thread_index_after.php
		$this->view->display('thread_index.htm');
	}
	
	//hook thread_control_after.php
}

?>