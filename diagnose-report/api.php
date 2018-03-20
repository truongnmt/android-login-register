<?php

	require_once 'dbconnect.php';

	//an array to display response
	$response = array();

	//if it is an api call
	//that means a get parameter named api call is set in the URL
	//and with this parameter we are concluding that it is an api call

	if(isset($_GET['apicall'])){

		switch($_GET['apicall']){

			case 'signup':
				//checking the parameters required are available or not
				if(isTheseParametersAvailable(array('username','email','password','gender'))){

					//getting the values
					$username = $_POST['username'];
					$email = $_POST['email'];
					$password = md5($_POST['password']);
					$gender = $_POST['gender'];

					//checking if the user is already exist with this username or email
					//as the email and username should be unique for every user
					$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
					$stmt->bind_param("ss", $username, $email);
					$stmt->execute();
					$stmt->store_result();

					//if the user already exist in the database
					if($stmt->num_rows > 0){
						$response['error'] = true;
						$response['message'] = 'User already registered';
						$stmt->close();
					}else{

						//if user is new creating an insert query
						$stmt = $conn->prepare("INSERT INTO users (username, email, password, gender) VALUES (?, ?, ?, ?)");
						$stmt->bind_param("ssss", $username, $email, $password, $gender);

						//if the user is successfully added to the database
						if($stmt->execute()){

							//fetching the user back
							$stmt = $conn->prepare("SELECT id, id, username, email, gender FROM users WHERE username = ?");
							$stmt->bind_param("s",$username);
							$stmt->execute();
							$stmt->bind_result($userid, $id, $username, $email, $gender);
							$stmt->fetch();

							$user = array(
								'id'=>$id,
								'username'=>$username,
								'email'=>$email,
								'gender'=>$gender
							);

							$stmt->close();

							//adding the user data in response
							$response['error'] = false;
							$response['message'] = 'User registered successfully';
							$response['user'] = $user;
						}
					}

				}else{
					$response['error'] = true;
					$response['message'] = 'required parameters are not available';
				}

			break;

			case 'login':
				//for login we need the username and password
				if(isTheseParametersAvailable(array('username', 'password'))){
					//getting values
					$username = $_POST['username'];
					$password = md5($_POST['password']);

					//creating the query
					$stmt = $conn->prepare("SELECT id, username, email, gender FROM users WHERE username = ? AND password = ?");
					$stmt->bind_param("ss",$username, $password);

					$stmt->execute();

					$stmt->store_result();

					//if the user exist with given credentials
					if($stmt->num_rows > 0){

						$stmt->bind_result($id, $username, $email, $gender);
						$stmt->fetch();

						$user = array(
							'id'=>$id,
							'username'=>$username,
							'email'=>$email,
							'gender'=>$gender
						);

						$response['error'] = false;
						$response['message'] = 'Login successfull';
						$response['user'] = $user;
					}else{
						//if the user not found
						$response['error'] = false;
						$response['message'] = 'Invalid username or password';
					}
				}
			break;

			default:
				$response['error'] = true;
				$response['message'] = 'Invalid Operation Called';
		}

	}else{
		//if it is not api call
		//pushing appropriate values to response array
		$response['error'] = true;
		$response['message'] = 'Invalid API Call';
	}

	//displaying the response in json structure
	echo json_encode($response);

	//function validating all the paramters are available
	//we will pass the required parameters to this function
	function isTheseParametersAvailable($params){

		//traversing through all the parameters
		foreach($params as $param){
			//if the paramter is not available
			if(!isset($_POST[$param])){
				//return false
				return false;
			}
		}
		//return true if every param is available
		return true;
	}
