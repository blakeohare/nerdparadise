<?
	function execute($request) {
		$output = array(
			'<h1 style="margin:0px;">Terms of Service</h1>',
			
			'<div style="font-size:12px; padding:20px;">',
			
			"<p>This site is provided just for fun. No quality of service is guaranteed. This site will break. Sometimes for long periods of time. You'll be okay. I promise.</p>",
			
			"<p>This site is privately owned and maintained. Freedom of speech does not apply. The moderators are your wardens. Any content (posts, comments, etc) can be removed for any reason whatsoever.</p>",
			
			"<p>Here are some possible reasons:</p>",
			'<ul>',
			"<li>The moderator deems your behavior as toxic to an inclusive and happy community.</li>",
			"<li>The moderator ate a really bad <a href=\"http://s3-media1.fl.yelpcdn.com/bphoto/06SlQIfYHJERTzzb3iM0lw/o.jpg\">breakfast burrito from a gas station</a> and now has a tummy ache and is taking it out on you.</li>",
			'</ul>',
			"<p>All moderator decisions are final. Even in the event of burrit'ocalypse '16. Please do not argue with them. Access to this site is not a right.</p>",
			
			"<p>Illegal content or discussion of illegal activities will be removed.</p>",
			
			"<p>Type tuba in the box on the registration form. But still, you should read the rest of this.</p>",
			
			"<p>If your parents join this site in order to keep tabs on you, please do not ask the moderator to ban them.</p>",
			
			"<p>Do not bash people for their choice of IDE or text editor.</p>",
			
			"<p>Be nice. That includes while on IRC as well.</p>",
			
			"<p>If you are banned, do not evade the ban. That makes us very mad. Your life will go on without us. I promise.</p>",
			
			"<h2>Stuff about Privacy</h2>",
			
			"<p>Your account information is not shared with anyone. Your account information isn't that interesting anyway.</p>",
			
			'</div>',
			);
			
		return build_response_ok('Terms of Service', implode("\n", $output));
		
			
	}
?>