<?php 
include($_SERVER['DOCUMENT_ROOT']."/opserv/php/config.php");

//only logged in people with rigths should be here
if(!logged_in_session() || !hasRights()){
  header("Location: /opserv/");
    exit;
}

if(!(isset($_GET['op']) && $_GET['op']=="pastOps")){
  $row = null;
    $result = mysql_query("SELECT * FROM operation WHERE id='".mysql_real_escape_string($_GET['id'])."' AND leader='".$_SESSION['userId']."'");
      //makes sure the user is a member and owns the operation or is a moderator
        if(!($row = mysql_fetch_assoc($result)) && $_SESSION['permission']!='4'){		
          	//this person does not belong here, send back
              	header("Location: /opserv/");
                  	exit;
        }
          //sloppy-hack
            $result = mysql_query("SELECT * FROM operation WHERE id='".mysql_real_escape_string($_GET['id'])."'");
              if(!($row = mysql_fetch_assoc($result))){		
                	//no operation - this person does not belong here, send back
                    	header("Location: /opserv/");
                        	exit;
              }
              
}

//reporting the aar:(a one-time event.. Any further changes are 'edits' and will not deduct points for non-attending RSVPers)
if(isset($_GET['op']) && isset($_GET['id']) && $_GET['op'] == "completeAAR"){
  $leaderResult = mysql_query("SELECT * FROM operation WHERE id='". mysql_real_escape_string($_GET['id']) ."' AND leader='". mysql_real_escape_string($_SESSION['userId']) ."'");
    //make sure this person has permisson to do this
      if($_SESSION['permission']=='4' || ($leaderRow = mysql_fetch_assoc($leaderResult))){
        	//mark completed/insert AAR
            	mysql_query("UPDATE operation SET completed='1', aar='". mysql_real_escape_string($_POST['aar']) ."' WHERE id='". mysql_real_escape_string($_GET['id']) ."'");
                	if(isset($_POST['member'])){
                    		foreach($_POST['member'] as $username){
                          			//process the result for this member
                                  			//get the userid
                                          			$idResult = mysql_query("SELECT * FROM members WHERE LOWER(username)='". mysql_real_escape_string(strtolower($username)) ."'");
                                                  			if($idRow = mysql_fetch_assoc($idResult)){
                                                          				//memberID operationID
                                                                    				memberAttended($idRow['id'], $_GET['id']);
                                                  			}
                    		}
                	}
                    	//once gone through all of the members attended, go through all remaining members, and subtract one from their profile if they signed up as 1
                        	$result = mysql_query("SELECT * FROM operation_attendees WHERE operation_id='". mysql_real_escape_string($_GET['id']) ."' AND status='1'");
                            	while($row = mysql_fetch_assoc($result)){
                                		mysql_query("UPDATE members SET points=(points-1) WHERE id='". mysql_real_escape_string($row['member_id'])."'");
                                      		mysql_query("UPDATE operation_attendees SET comments='did not show' WHERE id='". mysql_real_escape_string($row['id'])."'");
                            	}
      }
        header("Location: /opserv/operation_aar.php?op=pastOps");
          exit;
}

//editing the aar:
if(isset($_GET['op']) && isset($_GET['id']) && $_GET['op'] == "change"){
  $leaderResult = mysql_query("SELECT * FROM operation WHERE id='". mysql_real_escape_string($_GET['id']) ."' AND leader='". mysql_real_escape_string($_SESSION['userId']) ."'");
    //make sure this person has permisson to do this
      if($_SESSION['permission']=='4' || ($leaderRow = mysql_fetch_assoc($leaderResult))){
        	//mark completed/insert AAR
            	mysql_query("UPDATE operation SET aar='". mysql_real_escape_string($_POST['aar']) ."' WHERE id='". mysql_real_escape_string($_GET['id']) ."'");
                	
                    	//take care of removing members as attended
                        	if(isset($_POST['memberA'])){
                            		foreach($_POST['memberA'] as $username){
                                  			//process the result for this member
                                          			//get the userid
                                                  			$idResult = mysql_query("SELECT * FROM members WHERE LOWER(username)='". mysql_real_escape_string(strtolower($username)) ."'");
                                                          			if($idRow = mysql_fetch_assoc($idResult)){
                                                                  				//memberID operationID
                                                                            				memberUnattended($idRow['id'], $_GET['id']);
                                                          			}
                            		}
                        	}
                            	
                                	//take care of adding members as attended
                                    	if(isset($_POST['memberB'])){
                                        		foreach($_POST['memberB'] as $username){
                                              			//process the result for this member
                                                      			//get the userid
                                                              			$idResult = mysql_query("SELECT * FROM members WHERE LOWER(username)='". mysql_real_escape_string(strtolower($username)) ."'");
                                                                      			if($idRow = mysql_fetch_assoc($idResult)){
                                                                              				//memberID operationID
                                                                                        				memberAttended($idRow['id'], $_GET['id']);
                                                                      			}
                                        		}
                                    	}
      }
        header("Location: /opserv/operation_aar.php?op=pastOps");
          exit;
}


$pageTitle = "After Action Report";
$editor = true;
$autoComplete = true;
include($_SERVER['DOCUMENT_ROOT']."/opserv/php/head.php"); 


if(isset($row['name'])){
  if(isset($_GET['op']) && $_GET['op']=="edit"){
    	print "<h2 style=\"margin: 10px;\">You are editing the After Action Report for: ". $row['name'] ."</h2>\n";
  }else{
    	print "<h2 style=\"margin: 10px;\">You are giving the After Action Report for: ". $row['name'] ."</h2>\n";
  }
}
print "<div style=\"margin: 10px;\">\n";

  if((isset($_GET['op']) && $_GET['op']=="pastOps")){
    	printPastOps();
  }elseif(isset($_GET['id']) && !isset($_GET['op']) && $row['completed']=="0"){
    	//Write AAR/Select Member
        	print"<h3 style=\"color: #E35555;\">Verify Attendance and Write AAR</h3>\n";
            	print "<form name=\"aar\" action=\"operation_aar.php?id=".$row['id']."&op=completeAAR\" method=\"post\">\n";
                	$status = "";
                    	/*$aResult = mysql_query("
                        	SELECT memberId, username, status FROM
                            		(
                                  			SELECT members.id AS memberId, username, 9 AS status FROM members, operation_attendees WHERE  operation_attendees.member_id=members.id AND operation_id='". $row['id'] ."' AND status='1'
                                          				UNION
                                                    			SELECT members.id AS memberId, username, 8 AS status FROM members, operation_attendees WHERE  operation_attendees.member_id=members.id AND operation_id='". $row['id'] ."'  AND status='3'
                                                            				UNION
                                                                      			SELECT members.id AS memberId, username, 6 AS status FROM members, operation_attendees WHERE  operation_attendees.member_id=members.id AND operation_id='". $row['id'] ."'  AND status='2'
                                                                              				UNION
                                                                                        			SELECT members.id AS memberId, username, 7 AS status FROM members, member_games, operation WHERE operation.id='". $row['id'] ."' AND operation.game=member_games.game_id AND member_games.member_id=members.id 
                                                                                                				AND NOT members.id in (SELECT member_id from operation_attendees WHERE operation_attendees.member_id=members.id AND operation_id='". $row['id'] ."' AND (status='1' OR status='2' OR status='3'))
                                                                                                          		)
                                                                                                                		AS attendance GROUP BY memberId ORDER BY status DESC, username ASC");*/
                                                                                                                      		
                                                                                                                            		//Test Updated Query
                                                                                                                                               $aResult = mysql_query("SELECT M.id AS memberId,
                                                                                                                                                                                           M.username AS username,
                                                                                                                                                                                                                                       CASE
                                                                                                                                                                                                                                                                                   WHEN OA.status = '1' THEN '9'
                                                                                                                                                                                                                                                                                                                               WHEN OA.status = '2' THEN '6'
                                                                                                                                                                                                                                                                                                                                                                           WHEN OA.status = '3' THEN '8'
                                                                                                                                                                                                                                                                                                                                                                                                                       ELSE '7'
                                                                                                                                                                                                                                                                                                                                                                                                                                                                   END AS status
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          FROM member_games G
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 JOIN members M on M.id=G.member_id
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        JOIN operation O on O.game=G.game_id AND O.id='". $row['id'] ."'
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               LEFT JOIN operation_attendees OA ON M.id = OA.member_id AND OA.operation_id = '". $row['id'] ."'
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      ORDER BY status DESC, username ASC");
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        	while($aRow = mysql_fetch_assoc($aResult)){
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            		//print checkbox for each user
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  		if($status != $aRow['status']){
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        			if($aRow['status'] == "9"){
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                				print "<strong style=\"color: darkorange;\">Members RSVPed as Attending:</strong><br/>";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        			}elseif($aRow['status'] == "8"){
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                				print "<strong style=\"color: darkorange;\">Members RSVPed as Possibly Attending:</strong><br/>";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        			}elseif($aRow['status'] == "7"){
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                				print "<strong style=\"color: darkorange;\">Members that did not RSVP:</strong><br/>";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        			}elseif($aRow['status'] == "6"){
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                				print "<strong style=\"color: darkorange;\">Members RSVPed as Not Attending:</strong><br/>";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        			}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  		}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        		$status = $aRow['status'];
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              		print "<input type=\"checkbox\" name=\"member[]\" value=\"".$aRow['username']."\"/> ". $aRow['username'] . "<br/>\n";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        	}
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            	print "<br/><br/><strong style=\"color: darkorange;\">After Action Report: \n</strong><br/>";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                	print "<textarea id=\"edit_body\" name=\"aar\">". $row['aar'] ."</textarea>\n<br/>";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    	print "<br/><input type=\"submit\" name=\"Submit AAR\" value=\"Submit AAR\"/>	\n";
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        	print "</form>";
  }elseif(isset($_GET['id']) && isset($_GET['op']) && $_GET['op']!="completeAAR"){
    	//editing the operation
        	//Write AAR/Select Member
            	print"<h3 style=\"color: #E35555;\">Verify Attendance and Write AAR</h3>\n";
                	print "<form name=\"aar\" action=\"operation_aar.php?id=".$row['id']."&op=change\" method=\"post\">\n";
                    	$status = "";
                        	$attending = null;
                            	//$aResult = mysql_query("SELECT username, status FROM operation_attendees RIGHT JOIN members ON operation_attendees.member_id=members.id WHERE (operation_attendees.operation_id='".$row['id']."') OR isnull(operation_attendees.operation_id) ORDER BY status DESC, username ASC");
                                	/*$aResult = mysql_query("SELECT DISTINCT username, status FROM (
                                    	SELECT username, status FROM operation_attendees O, members M WHERE O.member_id=M.id AND O.operation_id='".$row['id']."'
                                        	UNION
                                            	SELECT username, 0 AS status FROM members M, member_games G, operation O WHERE O.id='".$row['id']."' AND O.game=G.game_id AND M.id=G.member_id AND NOT M.id in (SELECT M.id FROM operation_attendees O, members M WHERE O.member_id=M.id AND O.operation_id='".$row['id']."')
                                                	) AS U ORDER BY status DESC, username ASC");*/
                                                    	
                                                        	//Test Updated Query
                                                                  $aResult = mysql_query("SELECT 
                                                                                                M.username,
                                                                                                                              COALESCE(OA.status,'0') AS status
                                                                                                                                                              FROM member_games G
                                                                                                                                                                                              JOIN members M on M.id=G.member_id
                                                                                                                                                                                                                              JOIN operation O on O.game=G.game_id AND O.id='". $row['id'] ."'
                                                                                                                                                                                                                                                              LEFT JOIN operation_attendees OA ON M.id = OA.member_id AND OA.operation_id = '". $row['id'] ."'
                                                                                                                                                                                                                                                                                              ORDER BY status DESC, username ASC");
                                                                                                                                                                                                                                                                                                	while($aRow = mysql_fetch_assoc($aResult)){
                                                                                                                                                                                                                                                                                                    		//print checkbox for each user
                                                                                                                                                                                                                                                                                                          		if($status != $aRow['status']){
                                                                                                                                                                                                                                                                                                                			if($aRow['status'] == "4"){
                                                                                                                                                                                                                                                                                                                        				$attending = true;
                                                                                                                                                                                                                                                                                                                                  				print "<strong style=\"color: darkorange;\">Members Marked as Attending: (Check to set as not attending)</strong><br/>";
                                                                                                                                                                                                                                                                                                                			}elseif($attending){
                                                                                                                                                                                                                                                                                                                        				$attending = false;
                                                                                                                                                                                                                                                                                                                                  				print "<strong style=\"color: darkorange;\">Members Not Marked Attending (Check to set as attending):</strong><br/>";
                                                                                                                                                                                                                                                                                                                			}
                                                                                                                                                                                                                                                                                                          		}
                                                                                                                                                                                                                                                                                                                		$status = $aRow['status'];
                                                                                                                                                                                                                                                                                                                      		if($attending){
                                                                                                                                                                                                                                                                                                                            			print "<input type=\"checkbox\" name=\"memberA[]\" value=\"".$aRow['username']."\"/> ". $aRow['username'] . "<br/>\n";
                                                                                                                                                                                                                                                                                                                      		}else{
                                                                                                                                                                                                                                                                                                                            			print "<input type=\"checkbox\" name=\"memberB[]\" value=\"".$aRow['username']."\"/> ". $aRow['username'] . "<br/>\n";
                                                                                                                                                                                                                                                                                                                      		}
                                                                                                                                                                                                                                                                                                	}
                                                                                                                                                                                                                                                                                                    	print "<br/><br/><strong style=\"color: darkorange;\">After Action Report: \n</strong><br/>";
                                                                                                                                                                                                                                                                                                        	print "<textarea id=\"edit_body\" name=\"aar\">". $row['aar'] ."</textarea>\n<br/>";
                                                                                                                                                                                                                                                                                                            	print "<br/><input type=\"submit\" name=\"Submit AAR\" value=\"Submit AAR\"/>	\n";
                                                                                                                                                                                                                                                                                                                	print "</form>";
  }else{
    	//header("Location: /opserv/");
        	//exit;
  }
    
      if(isset($_GET['id'])){
        	?>
            	<script type="text/javascript">
                		CKFinder.setupCKEditor( null, '/ckfinder/' );
                      		var editor = CKEDITOR.replace( 'edit_body',
                            			{
                                    				toolbar : 'Westminster',
                                              				filebrowserBrowseUrl : '/ckfinder/ckfinder.html',
                                                        				filebrowserImageBrowseUrl : '/ckfinder/ckfinder.html?type=Images',
                                                                  				filebrowserUploadUrl : 
                                                                            			   '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files&currentFolder=../user/',
                                                                                       				filebrowserImageUploadUrl : 
                                                                                                 			   '/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images&currentFolder=../user/',
                            			});
                                    	</script>
                                        	<?php
      }
        ?>
          </div>
          <?php
            include($_SERVER['DOCUMENT_ROOT']."/opserv/php/footer.php"); 
            ?>
