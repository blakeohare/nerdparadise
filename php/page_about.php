<?
	
	function execute($request) {
		
		$output = array('<h1>About</h1>');
		
		$items = array(
			array(
				'What is this, exactly?', 'wat',
				"Nerd Paradise is a site for programmers to learn, play, and interact casually or competitively."),
			
			array(
				"What are points?", 'points',
				"Various activities on the site will award you points. At the end of each season, the person with the most points wins. "),
			
			array(
				"I have an idea for a golf problem!", 'problemidea',
				"Great. <a href=\"/contact\">Let me know</a>."),
			
			array(
				"I want to contribute a tutorial", 'tutorailidea',
				"At this time I am only posting tutorials I have personally written for reasons."),
			
			array(
				"I'm disatisfied. I want to take your source code and make my own NP. With blackjack. And hookers.", 'blackjack',
				"Neat. The source code for both the site and the auto-grader is available <a href=\"https://github.com/blakeohare/nerdparadise\">here</a> and ready for forking."),
			
			array(
				"Wasn't NP a totally differenet site? Where did it go?", 'oldnp',
				"Yes. This is version 10 of the website. The content from the previous version (version 8) was mostly rolled into my <a href=\"http://blakeohare.com\">personal blog</a> since that's really all it was anyway. Yes, the version numbers are a Windows joke."),
			
			array(
				"The autograder is down/unresponsive.", 'autograder',
				"That's not a question. The auto-grader is a sandboxed program that I am running on a dedicated machine in my apartment on my personal internet connection. It'll happen from time to time."),
			
			array(
				"What the hell is Crayon and why is it everywhere?", 'crayon',
				"Crayon is a programming language I created primarily for creating games. If you're interested in learning it, feel free to wander over to <a href=\"http://crayonlang.org\">crayonlang.org</a>. The reason why it's everywhere is because I know how to sandbox it easily and so making an autograder for it was simple. I also like promoting it since I think it's a swell language."),
			
			array(
				"Has anyone actually asked you to ban their parents like the ToS mention?", 'parents',
				"Yes. Twice."),
			
			array(
				"Who are you?", 'who',
				"My name is Blake. I am a professional software engineer and enjoy writing programming languages, creating games, and teaching programming."),
			
			array(
				"How is it that this site can both be free and not have any ads?", 'adfree',
				"The server costs for this and my other sites are currently miniscule. I do this as a hobby."),
		);

		array_push($output, '<div style="padding:8px; margin-bottom:20px;">');
		foreach ($items as $item) {
			$heading = $item[0];
			$bookmark = $item[1];
			
			array_push($output, '<div><a href="#'.$bookmark.'">'.htmlspecialchars($heading).'</a></div>');
		}
		array_push($output, '</div>');
		
		foreach ($items as $item) {
			$heading = $item[0];
			$bookmark = $item[1];
			$text = $item[2];
			
			array_push($output, '<h2><a name="'.$bookmark.'"></a>'.htmlspecialchars($heading).'</h2>');
			array_push($output, '<div style="padding:8px; margin-bottom:20px;">');
			array_push($output, $text);
			array_push($output, '</div>');
		}
		return build_response_ok("About", implode("\n", $output));
	}
?>