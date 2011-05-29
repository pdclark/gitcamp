<?php

class Basecamp 
{

	public $connect;
	public $apitoken;
	public $base;
	public $cache;

	public function __construct($subdomain, $apitoken)
	{
		$this->base = 'https://'.$subdomain.'.basecamphq.com';
		$this->apitoken = $apitoken;
		
		$this->cache = Zend_Cache::factory('Output', 'File', 
			array( // Frontend Options
				'lifetime' => 60 * 5, // seconds * minutes
				'automatic_serialization' => true,
			),
			array( // Backend Options
				// 'cache_dir' => APPLICATION_PATH.'/tmp',
				'cache_dir' => sys_get_temp_dir(),
			)
		);
	}

	/*======================/
	 * 	General
	/*=====================*/
	
	// Returns the info for the company referenced by id
	function company($id) {
		return $this->hook("/contacts/company/{$id}","company");
	}
	
	// This will return an alphabetical list of all message categories in the referenced project.
	function message_categories($project_id) {
		return $this->hook("/projects/{$project_id}/post_categories","post-category");
	}
	
	// This will return an alphabetical list of all file categories in the referenced project.
	function file_categories($project_id) {
		return $this->hook("/projects/{$project_id}/attachment_categories","attachment-category");
	}
	
	// This will return a list of all active, 
	// on-hold, and archived projects that you have access to. The list is not ordered.
	function projects() {
	  	return $this->hook("/projects.xml","project");
	}
	
	/*======================/
	 * 	People
	/*=====================*/

	// Returns the currently logged in person (you).
	function current_person() {
		return $this->hook("/me.xml",'person');
	}

	// Returns all people visible to (and including) the requesting user.
	function people() {
		return $this->hook("/people.xml","person");
	}
	
	// Returns all people with access to the given project.
	function people_in_project($project_id) {
		return $this->hook("/projects/{$project_id}/people.xml","person");
	}

	// Returns a single person identified by their integer ID.
	function person($person_id) {
		return $this->hook("/contacts/person/{$person_id}","person");
	}

	/*======================/
	 * 	Messages and Comments
	/*=====================*/
	
	// Retrieve a specific comment by its id.
	function comment($comment_id) {
		return $this->hook("/msg/comment/{$comment_id}","comment");
	}

	// Return the list of comments associated with the specified message.
	function comments($comment_id) {
		return $this->hook("/msg/comments/{$message_id}","comment");
	}

	// Create a new comment, associating it with a specific message.
	function create_comment($comment) {
		return $this->hook("/msg/create_comment","comment",array("comment" => $comment), 'PUT');
	}
	
	// Creates a new message, optionally sending notifications to a selected list of people. 
	// Note that you can also upload files using this function, but you need to upload the 
	// files first and then attach them. See the description at the top of this document for more information.
	// notify should be an array of people_id's
	function create_message($project_id, $message, $notify = false) {
		$request['post'] = $message;
		if ($notify) {$request['notify'] = $notify;}
		return $this->hook("/projects/{$project_id}/msg/create","post",$request, 'PUT');
	}
	
	// Delete the comment with the given id.
	function delete_comment($comment_id) {
		return $this->hook("/msg/delete_comment/{$comment_id}","comment");
	}
	
	// Delete the message with the given id.
	function delete_message($message_id) {
		$request['post'] = 'delete';
		return $this->hook("/msg/delete/{$message_id}","post", $request);
	}
	
	// This will return information about the referenced message. 
	// If the id is given as a comma-delimited list, one record will be 
	// returned for each id. In this way you can query a set of messages in a 
	// single request. Note that you can only give up to 25 ids per request--more than that will return an error.
	function message($message_ids) {
		return $this->hook("/msg/get/{$message_ids}","post");
	}
	
	// This will return a summary record for each message in a project. 
	// If you specify a category_id, only messages in that category will be returned. 
	// (Note that a summary record includes only a few bits of information about a post, not the complete record.)
	function message_archive($project_id,$category_id = false) {
		$request['post']['project-id']  = $project_id;
		if ($category_id) { $request['post']['category-id'] = $category_id; }
		return $this->hook("/projects/{$project_id}/msg/archive","post",$request);
	}
	
	// This will return a summary record for each message in a particular category. 
	// (Note that a summary record includes only a few bits of information about a post, not the complete record.)
	function message_archive_per_category($project_id,$category_id) {
		return $this->hook("/projects/{$project_id}/msg/cat/{$category_id}/archive","post");
	}
	
	
	// Update a specific comment. This can be used to edit the content of an existing comment.
	function update_comment($comment_id,$body) {
		$comment['comment_id'] = $comment_id;
		$comment['body'] = $body;
		return $this->hook("/msg/update_comment","comment",$comment, 'PUT');
	}
	
	// Updates an existing message, optionally sending notifications to a selected list of people. 
	// Note that you can also upload files using this function, but you have to format the request 
	// as multipart/form-data. (See the ruby Basecamp API wrapper for an example of how to do this.)
	function update_message($message_id,$message,$notify = false) {
		$request['post'] = $message;
		if ($notify) {$request['notify'] = $notify;}
		return $this->hook("/msg/update/{$message_id}","post",$request, 'PUT');
	}
	
	
	
	/*======================/
	 * 	Todo Lists and Items
	/*=====================*/
	
	// Marks the specified item as "complete". If the item is already completed, this does nothing. 
	function complete_item($item_id) {
		return $this->hook("/todos/complete_item/{$item_id}","todo-item");
	}
	
	// This call lets you add an item to an existing list. The item is added to the bottom of the list. 		
	// 	If a person is responsible for the item, give their id as the party_id value. If a company is 
	// 	responsible, prefix their company id with a 'c' and use that as the party_id value. If the item 
	// 	has a person as the responsible party, you can use the notify key to indicate whether an email 
	// 	should be sent to that person to tell them about the assignment.	
	function create_item($list_id, $item, $responsible_party = false, $notify_party = false) {
		$request['content'] = $item;
		if ($responsible_party) {
			$request['responsible_party'] = $responsible_party;
			$request['notify'] = ($notify_party)?"true":"false";
		}
		return $this->hook("/todos/create_item/{$list_id}","todo-item",$request, 'PUT');
	}
	
	// This will create a new, empty list. You can create the list explicitly, 
	// or by giving it a list template id to base the new list off of.
	function create_list($project_id,$list) {
		return $this->hook("/projects/{$project_id}/todos/create_list","todo-list",$list, 'PUT');
	}
	
	// Deletes the specified item, removing it from its parent list.
	function delete_item($item_id) {
		return $this->hook("/todos/delete_item/{$item_id}","todo-item");
	}
	
	// This call will delete the entire referenced list and all items associated with it. 
	// Use it with caution, because a deleted list cannot be restored!
	function delete_list($list_id) {
		return $this->hook("/todos/delete_list/{$list_id}","todo-list");
	}
	
	// This will return the metadata and items for a specific list.
	function list_items($list_id) {
		return $this->hook("/todos/list/{$list_id}","todo-list");
	}
	
	// This will return the metadata for all of the lists in a given project. 
	// You can further constrain the query to only return those lists that are "complete"
	// (have no uncompleted items) or "uncomplete" (have uncompleted items remaining).
	function lists($project_id, $complete = false) {
		$request['complete'] = ($complete)?"true":"false";
		return $this->hook("/projects/{$project_id}/todos/lists","todo-list", $request);
	}
	
	// Changes the position of an item within its parent list. It does not currently 
	// support reparenting an item. Position 1 is at the top of the list. Moving an 
	// item beyond the end of the list puts it at the bottom of the list.
	function move_item($item_id,$to) {
		return $this->hook("/todos/move_item/{$item_id}","todo-item",array('to' => $to));
	}
	
	// This allows you to reposition a list relative to the other lists in the project.
	// A list with position 1 will show up at the top of the page. Moving lists around lets 
	// you prioritize. Moving a list to a position less than 1, or more than the number of 
	// lists in a project, will force the position to be between 1 and the number of lists (inclusive).
	function move_list($list_id,$to) {
		return $this->hook("/todos/move_list/{$list_id}","todo-list",array('to' => $to));
	}
	
	// Marks the specified item as "uncomplete". If the item is already uncompleted, this does nothing. 
	function uncomplete_item($item_id) {
		return $this->hook("/todos/uncomplete_item/{$item_id}","todo-item");
	}
	
	// Modifies an existing item. 
	// The values work much like the "create item" operation, so you should refer to that for a more detailed explanation.
	function update_item($item_id, $item, $responsible_party = false, $notify_party = false) {
		$request['item']['content'] = $item;
		if ($responsible_party) {
			$request['responsible_party'] = $responsible_party;
			$request['notify'] = ($notify_party)?"true":"false";
		}
		return $this->hook("/todos/update_item/{$item_id}","todo-item",$request, 'PUT');
	}
	
	// With this call you can alter the metadata for a list.
	function update_list($list_id,$list) {
		return $this->hook("/todo_lists/{$list_id}.xml","todo-list",array('todo-list' => $list), 'PUT');
	}
	
	/*======================/
	 * 	Time Tracking
	/*=====================*/
	
	// Returns a page full of time entries for the given project, in descending order by date. 
	// Each page contains up to 50 time entry records. To select a different page of data, 
	// set the “page” query parameter to a value greater than zero. The X-Records HTTP header
	// will be set to the total number of time entries in the project, X-Pages will be set 
	// to the total number of pages, and X-Page will be set to the current page.
	function project_entries($project_id) {
		return $this->hook("/projects/{$project_id}/time_entries.xml","time-entry");
	}
	
	
	// Creates a new time entry for the given todo item.
	function create_time_entry_for_item($item_id, $params) {
		return $this->hook("/todo_items/$item_id/time_entries.xml",'time-entry', array('time-entry' => $params), 'POST');
	}
	
	
	/*======================/
	 * 	Milestones
	/*=====================*/
	
	// Marks the specified milestone as complete.
	function complete_milestone($milestone_id) {
		return $this->hook("/milestones/complete/{$milestone_id}","milestone");
	}
	
	// Creates a single or multiple milestone(s). 
	function create_milestones($project_id,$milestones) {
		return $this->hook("/projects/{$project_id}/milestones/create","milestone",array("milestone" => $milestones));
	}
	
	// Deletes the given milestone from the project.
	function delete_milestone($milestone_id) {
		return $this->hook("/milestones/delete/{$milestone_id}","milestone");
	}
	
	// This lets you query the list of milestones for a project. 
	// You can either return all milestones, or only those that are late, completed, or upcoming.
	function list_milestones($project_id, $find = "all") {
		return $this->hook("/projects/{$project_id}/milestones/list","milestone",array('find' => $find));
	}
	
    // Modifies a single milestone. You can use this to shift the deadline of a single milestone, 
	// and optionally shift the deadlines of subsequent milestones as well. 
	function uncomplete_milestone($milestone_id) {
		return $this->hook("/milestones/uncomplete/{$milestone_id}","milestone", 'PUT');
	}
	
	// Creates a single or multiple milestone(s). 
	function update_milestone($milestone_id,$milestone,$move_upcoming_milestones = true,$move_upcoming_milestones_off_weekends = true) {
		$request['milestone'] = $milestone;
		$request['move-upcoming-milestones'] = $move_upcoming_milestones;
		$request['move-upcoming-milestones-off-weekends'] = $move_upcoming_milestones_off_weekends;
		return $this->hook("/milestones/update/{$milestones_id}","milestone",array("milestone" => $milestone), 'PUT');
	}
	
	/*===================/
	 * 	The Worker Bees  
	/*===================*/
	
	function hook($url, $expected, $params = false, $method = 'GET' ) {
		$returned = $this->request($url,$params, $method);
		
		// Return all elements inside the root element
		// e.g., all <project> elements inside <projects> wrapper
		// $filtered = $returned->xpath( '//'.$expected );

		return $returned;
		
		//	$placement = $expected;
		//	if (isset($returned->{$expected})) {
		//		$this->{$placement} = $returned->{$expected};	
		//		return $returned->{$expected};
		//	} else {
		//		$this->{$placement} = $returned;
		//		return $returned;
		//	}
	}
	
	function request($url, $params = false, $method = 'GET') {
		$client = new Zend_Http_Client();
		
		$client->setAuth($this->apitoken, 'password');
		$client->setUri($this->base.$url);
		$client->setHeaders( array(
			'Accept' => 'application/xml',
			'Content-Type' => 'application/xml',
		) );

		if ( $params ) { // Sending data?
			$xml = $this->array_to_xml( $params, new SimpleXMLElement('<request/>') );
			$client->setRawData( $xml->asXML() );
		}

		$request_id = md5( $url . serialize($params) );
		
		// Check Cache
		if( ($output = $this->cache->load( $request_id )) === false ) {
		    // No Cache
			// HTTP Request
			try {
				$response = $client->request( $method );

				if ( $response->getStatus() == 200 || $response->getStatus() == 201 ) {
					if ($method == 'PUT' || $method == 'POST') { 
						return true;
					}
					
					$this->cache->save( $response->getBody(), $request_id );
					$output = simplexml_load_string( $response->getBody() );
				} 
				else { 
					echo $this->base.$url . "\n";
					echo $response->getStatus() . ": " . $response->getMessage() . "\n";
					return false;
				}

			}catch (Exception $e) {
				echo $e;
				return false;
			}

		} else {
			// Cache found
		    // echo "This one is from cache!\n\n";
			$output = simplexml_load_string( $output );
		}
		
		return $output; // SimpleXML object
	}
	
	function array_to_xml(array $arr, SimpleXMLElement $xml ) {
	    foreach ($arr as $k => $v) {
	        is_array($v)
	            ? $this->array_to_xml($v, $xml->addChild($k))
	            : $xml->addChild($k, $v);
	    }
	    return $xml;
	}
	
}

?>
