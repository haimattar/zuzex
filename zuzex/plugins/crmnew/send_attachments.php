<?php
		  include('../../../wp-load.php');
		 $id = $_POST['id'];
		 $filenos = $_POST['filenos'];
		 $docs = $_POST['docs'];
		 $siteurl = get_option("siteurl");
		 $current_user_id = get_current_user_id();
		 $user = new WP_User( $current_user_id );
		 foreach( $user->roles as $role ) {
					$role = get_role( $role );
			if ( $role != null )
			 $userroles = $role->name;
		  }
		 global $wpdb;	
		 // print_r($_FILES);exit;
		 if ( 0 < $_FILES['file']['error'] ) {
			$errors = 'Error: ' . $_FILES['file']['error'] . '<br>';
			$array_response = array("docs"=>"");				 
			echo json_encode($array_response);
		}else{
			if (!file_exists(ABSPATH.'wp-content/plugins/crm/files/'.$filenos)) {
				 mkdir(ABSPATH.'wp-content/plugins/crm/files/'.$filenos, 0755, true);
			}
			
			$file_name = $_FILES['file']['name'];
			$target_path = ABSPATH.'wp-content/plugins/crm/files/'.$filenos.'/' . $docs."-".$_FILES['file']['name'];
			move_uploaded_file($_FILES['file']['tmp_name'], $target_path);
			 $documents = $siteurl."/wp-content/plugins/crm/files/".$filenos."/".$docs."-".$file_name;
			 //var_dump('test'); die; Commented as it interrupts file uploading process
			 //======SEND FILES TO GOOGLE DRIVE========//
			 $googledriveurl = send_google_drive($id,$filenos,$target_path,$docs);
			 //======SEND FILES TO GOOGLE DRIVE========//
			 //===Remove Image From Folder===//
				$SQLEXISTS = "SELECT ".$docs." FROM  wp_clientinfo
					WHERE id='".$id."'";
				$rsExists = $wpdb->get_results($SQLEXISTS,ARRAY_A);
				$files = $rsExists[0][$docs];
				
				@unlink(ABSPATH.'wp-content/plugins/crm/files/'.$filenos.'/'.basename($target_path));
				
				$directory = ABSPATH.'wp-content/plugins/crm/files/'.$filenos;
				rmdir($directory);
			 //===Remove Image From Folder===//
			 //======Get Dicusignindocs======//
			   $SQLDOCS = "SELECT envelopeId,usertype FROM  wp_clientinfo WHERE id='".$id."'";
			   $rsDocs = $wpdb->get_results($SQLDOCS);
			   $updatedby = $rsDocs[0]->usertype;
			  
			  $SQLUPDATE = "UPDATE wp_clientinfo SET ".$docs."='".esc_sql($googledriveurl)."',".$docs."updatedby='".$userroles."' WHERE id='".$id."'";
			  $wpdb->query($SQLUPDATE);
			 //======Get Dicusignindocs======//			
			 //==Send Mail==//
			$SQL = "SELECT ".$docs." FROM  wp_clientinfo WHERE id='".$id."'";
			$rs = $wpdb->get_results($SQL,ARRAY_A);
			 
			$docs = '<a href="'. $rs[0][$docs].'" target="_blank"><h4><img class="greentickmark" src="'.plugins_url("crmnew/images/img4.png" , dirname(__FILE__)).'"></h4></a>';
			$array_response = array("senddates"=>date("d/m/Y"),"docs"=>$docs);					 
			echo json_encode($array_response);
		}
		
		
		//======Send To Google Drive======//
		function send_google_drive($id,$fileno,$filename,$docs){
          
          	global $wpdb;
			$filenamearray = array("docclassifieds"=>"PRECARITE JUSTIFICATIF - ".$fileno."","document8"=>"DEVIS - ".$fileno."","document9"=>"FACTURE - ".$fileno."","document10"=>"ATTESTATION SUR L’HONNEUR - ".$fileno."","document11"=>"RGE certificate - ".$fileno."");
			require(ABSPATH.'/wp-content/themes/enemat/googledrives/vendor/autoload.php');
			$client = getClient();
			if($docs!="document11"){
				$parentfolders = get_option("parentfolders");
				$crmfolders = get_option("crmfolders");
				$service = new Google_Service_Drive($client);
				try{
				//===Root Folder's Sub Folder======//
				$results = $service->files->listFiles(
				['q' => "name = '$parentfolders' and mimeType = 'application/vnd.google-apps.folder'"] // Get only folder with name equal $parentfolders value
			);
			/*print_r("<pre>");
			print_r($results);exit; Commented as it interrupts file uploading process */
				$parents = "";
				foreach ($results->getFiles() as $item) {
					if ($item['name'] == $parentfolders) {
							$parents = $item['id'];
							break;
					}
				}
				
				 $optParams = array(
					'pageSize' => 10,
					'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
					'q' => "'".$parents."' in parents"
				);
			   $results = $service->files->listFiles($optParams);
			   $parentsarray = array();
			   foreach ($results->getFiles() as $item) {
					$parentsarray[$item['name']] = $item->getId();		  
			   }
			   $parentid = $parentsarray[$crmfolders];
			//===Root Folder's Sub Folder======//
			//===Sub Folder's Folders======//
			$optParams1 = array(
				'pageSize' => 10,
				'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
				'q' => "'".$parentid."' in parents"
			);
			$results = $service->files->listFiles($optParams1);
			//===Sub Folder's Folders======//	
			//==========CREATE FOLDER====//
			$childid = "";
			foreach ($results->getFiles() as $item) {
				if ($item['name'] == $fileno) {
					$childid = $item['id'];
						break;
					}
			}
				if(empty($childid)){
					$fileMetadata = new Google_Service_Drive_DriveFile(array(
										'name' => $fileno,
										'parents'=>array($parentid),
										'mimeType' => 'application/vnd.google-apps.folder'));
										$file = $service->files->create($fileMetadata, array(
										'fields' => 'id'));
					 $folderId = $file->id;
				 }else{
					$folderId = $childid;
				 }
				 if(!empty($filename)){
						try{
						$fileMetadata = new Google_Service_Drive_DriveFile(array(
									'name' => $filenamearray[$docs],
									'parents' => array($folderId)
								));
						$content = file_get_contents($filename);
						$files = $service->files->create($fileMetadata, array(
										'data' => $content,
										'uploadType' => 'resumable',
										'fields' => 'id'));	
						$fileids = $files->id;
						}catch (Google_ServiceException $e) {
					   $file_save = false;
					   file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
					}catch (Google_IOException $e) {
					  $file_save = false;
					  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
					} catch (Google_Exception $e) {
					  $file_save = false;
					  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
					}catch (Exception $e) {
						$file_save = false;
						file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
					}
				}
				$driveurls = "https://drive.google.com/open?id=".$fileids."";
				$newPermission = new Google_Service_Drive_Permission();
				$newPermission->setType('anyone');
				$newPermission->setRole('reader');
				$service->permissions->create($fileids, $newPermission);
				}catch (Google_ServiceException $e) {
				   $file_save = false;
				   file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}catch (Google_IOException $e) {
				  $file_save = false;
				  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				} catch (Google_Exception $e) {
				  $file_save = false;
				  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}catch (Exception $e) {
					$file_save = false;
					file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}
			}else if($docs=="document11"){
				$SQL = "SELECT pucompany_name,pusociety_number FROM  wp_clientinfo
					WHERE id='".$id."'";
				$rs = $wpdb->get_results($SQL);
				$parentfolders = get_option("parentfolders");
				$rgefolders = get_option("rgefolders");
				$filenames = "RGE CERTIFICATE ".stripslashes($rs[0]->pucompany_name)." ".stripslashes($rs[0]->pusociety_number)." - 41";
				
				try{
				$service = new Google_Service_Drive($client);
				//===Root Folder's Sub Folder======//
			$results = $service->files->listFiles(
				['q' => "name = '$parentfolders' and mimeType = 'application/vnd.google-apps.folder'"] // Get only folder with name equal $parentfolders value
			);
			$parents = "";
			foreach ($results->getFiles() as $item) {
				if ($item['name'] == $parentfolders) {
						$parents = $item['id'];
						break;
				}
			}
			
			$optParams = array(
				'pageSize' => 10,
				'fields' => "nextPageToken, files(contentHints/thumbnail,fileExtension,iconLink,id,name,size,thumbnailLink,webContentLink,webViewLink,mimeType,parents)",
				'q' => "'".$parents."' in parents"
			);
		   $results = $service->files->listFiles($optParams);
		   $parentsarray = array();
		   foreach ($results->getFiles() as $item) {
				$parentsarray[$item['name']] = $item->getId();		  
		   }
		   $folderId = $parentsarray[$rgefolders];
		  //===Root Folder's Sub Folder======//				 
				if(!empty($filename)){
					try{
						$fileMetadata = new Google_Service_Drive_DriveFile(array(
									'name' => $filenames,
									'parents' => array($folderId)
								));
						$content = file_get_contents($filename);
						$files = $service->files->create($fileMetadata, array(
										'data' => $content,
										'uploadType' => 'resumable',
										'fields' => 'id'));	
						$fileids = $files->id;
					}catch (Google_ServiceException $e) {
				   $file_save = false;
				   file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}catch (Google_IOException $e) {
				  $file_save = false;
				  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				} catch (Google_Exception $e) {
				  $file_save = false;
				  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}catch (Exception $e) {
					$file_save = false;
					file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}
				}
				$driveurls = "https://drive.google.com/open?id=".$fileids."";
				$newPermission = new Google_Service_Drive_Permission();
				$newPermission->setType('anyone');
				$newPermission->setRole('reader');
				$service->permissions->create($fileids, $newPermission);
				}catch (Google_ServiceException $e) {
				   $file_save = false;
				   file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}catch (Google_IOException $e) {
				  $file_save = false;
				  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				} catch (Google_Exception $e) {
				  $file_save = false;
				  file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}catch (Exception $e) {
					$file_save = false;
					file_put_contents(ABSPATH."wp-content/themes/enemat/ajax_scripts/error_logs.txt",$e->getMessage(),FILE_APPEND);
				}
			}
			return $driveurls;		 
			 
		}
		function getClient(){
			$client = new Google_Client();
			$client->setApplicationName('Google Drive API PHP Quickstart');
			$client->setScopes(Google_Service_Drive::DRIVE);
			$client->setAuthConfig(ABSPATH.'/wp-content/themes/enemat/googledrives/credentials.json');
			return $client;
		}
		//======Send To Google Drive======//
		 ?>