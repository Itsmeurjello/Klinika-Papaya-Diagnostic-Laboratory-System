<?php
	require 'config/db_connect.php';

	if(isset($_POST['login'])) {
		$errMsg = '';

		$username = $_POST['username'];
		$password = $_POST['password'];
 

		if($username == '')
			$errMsg = 'Enter username';
		if($password == '')
			$errMsg = 'Enter password';

		if($errMsg == '') {
			try {
				$stmt = $connect->prepare('SELECT  username, password, role FROM users WHERE username = :username');
				$stmt->execute(array(
					':username' => $username
					));
				$data = $stmt->fetch(PDO::FETCH_ASSOC);
                            
				if($data == false){
					$errMsg = "User $username not found.";
				}
				else {
					// uncomment this later, comment ko muna for testing purpose.
					//$password = hash('sha256', $_POST['password']);
					// echo $password;
					// echo("<br>");
					// echo $data['password'];
					$password = $_POST['password'];
					if($password == $data['password']) {
						
					
						$_SESSION['username'] = $data['username'];
						// $_SESSION['password'] = $data['password'];
                        $_SESSION['role'] = $data['role'];
						header('Location: dashboard.php');
						exit();
						//  echo("Log in successfully");
						
						
					}
					else
						$errMsg = 'Password not match.';
						//  echo("error message");
					
				}
			}
			catch(PDOException $e) {
				$errMsg = $e->getMessage();
			}
		}
	}
?>