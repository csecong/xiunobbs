
// 5 分钟内最大发帖量 20
if($this->_user['groupid'] > 5) {
	$maxposts = 5; 
	
	$mypostlist = $this->mypost->get_list_by_uid($uid, 1, $maxposts);
	
	if(count($mypostlist) >= $maxposts) {
	
		$last = array_pop($mypostlist);
		$lastpost = $this->post->read($last['fid'], $last['pid']);
		if($_SERVER['time'] - $lastpost['dateline'] < 300) {
			$this->message('系统启用了防止灌水插件，5分钟内发帖量不能超过5篇，如果对您带来麻烦，我们表示抱歉。', 0);
		}
	}
	
	// 最新贴里如果连续超过3篇，认为在灌水。
	$newposts = 0;
	$threadlist = $this->thread->get_newlist(0, 5);
	foreach($threadlist as $_thread) {
		if($_thread['uid'] == $uid) {
			$newposts++;
		}
	}
	if($newposts == 5) {
		//$this->message('系统启用了防止灌水插件，探测到您在频繁发贴，发贴请求被拒绝。', 0);
		$this->message('系统启用了防止灌水插件，连续发贴不能超过5篇，如果对您带来麻烦，我们表示抱歉。', 0);
	}
}
	