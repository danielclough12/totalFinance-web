<?php

/**
 * Handles user interactions within the app
 * 
 * PHP version 5
 */
class Users
{
	/**
	 * The database object
	 * 
	 * @var object
	 */
	private $_db;

	/**
	 * Checks for a database object and creates one if none is found
	 * 
	 * @param object $db
	 * @return void
	 */
	public function __construct($db=NULL)
	{
		if(is_object($db))
		{
			$this->_db = $db;
		}
		else
		{
			$dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME;
			$this->_db = new PDO($dsn, DB_USER, DB_PASS);
		}
	}


	/**
	 * Checks credentials and logs in the user
	 * 
	 * @return object that includes:
     *    user info or err info
     *    TBD: maybe also include user expense 
     *    & budget data in the object?
	 */
	public function accountLogin()
	{
        $result = new stdClass();
        $email = $_POST['email'];
        $password = $_POST['password'];
      
		$sql = "SELECT *
	    		FROM users
	    		WHERE email=:email
	    		LIMIT 1";
        try
	    {
	    	$stmt = $this->_db->prepare($sql);
	    	$stmt->bindParam(':email', $email, PDO::PARAM_STR);
	    	$stmt->execute();
		$row = $stmt->fetch();
          
       		// If email found in user table check verification code
	    	if($stmt->rowCount()==1) {
			
			if(($row['verified']) == 0) {
				$result->pass = FALSE;
				$result->err_msg = "Error: The account "
					. "has not yet been verified."
					. " Please check your email for a "
					. "verification link.";

	    		return $result;
	    	}
	    	else if ($row['hashedPW'] === MD5($password)) {
			// this is from the original - not sure what it does
 	    		//$_SESSION['email'] = htmlentities($_POST['email'], ENT_QUOTES);
 	    		//$_SESSION['LoggedIn'] = 1;
			return $stmt->fetchObject(); }
	    	else {
                $result->err_msg = "Error: The email address "
                                  . "or password is incorrect."
                                  . " Please try again or create "
                                  . "a new account.";
	    		return $result;
	    	}
		$result->pass = TRUE;
		$result->user = $row;
			return $result;
		}
	    }
		
		// Otherwise if no match found or pw is incorrect, alert user
		$result->pass = FALSE;
		$result->err_msg = "ERROR: The email address or password "
			. "is incorrect or an account has not "
			. "yet been set up for this email address."
			. " Please try again or create "
			. "a new account.";
		return $result;
	}
		
	    catch(PDOException $e)
	    {
	    $result->pass = FALSE;
            $result->err_msg = "Error: Unable to log in. "
                              . "Please try again later.";
            return $result;
	    }
	}
  
	/**
	 * Creates account & sends email for user verification
	 * 
	 * Discuss: Prefer to return JSON objects or 
     * maybe JSON & strings instead of any HTML
     * so that we can stick with SPA...
     * Question: why does accountLogin use try catch 
     * & original createAccount doesn't?
	 */

    public function createAccount()
	{
		$email = trim($_POST['email']);
        $name = trim($_POST['name']);
        $result = new stdClass();
      
        // Use hashed timestamp when sending ver. email
		$v = sha1(time());
		
		$sql = "SELECT COUNT(email) AS theCount
				FROM users
				WHERE email=:email";

		if($stmt = $this->_db->prepare($sql)) {
			$stmt->bindParam(":email", $email, PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch();
			if($row['theCount']!=0) {
		$result->pass = FALSE;
                $result->err_msg = "Error: "
					. "Sorry, that email is already in use. "
					. "Please try again.";
                return $result;
			}
			// Implement if stmt below to check if sendVerificationEmail returns error
			 $result->misc = $this->sendVerificationEmail($email, $name, $v);
//			if(!$this->sendVerificationEmail($email, $name, $v)) {
//              $result->pass = FALSE;
//				$result->err_msg = "Error: "
//					. "There was an error sending your"
//					. " verification email. Please "
//					. "contact "
//					. "us for support. We apologize for the "
//					. "inconvenience.";
//               return $result;
//			}
			$stmt->closeCursor();
		}
		
		$sql = "INSERT INTO users(email, ver_code, name)
				VALUES(:email, :ver, :name)";
		if($stmt = $this->_db->prepare($sql)) {
			$stmt->bindParam(":email", $email, PDO::PARAM_STR);
			$stmt->bindParam(":ver", $v, PDO::PARAM_STR);
            $stmt->bindParam(":name", $name, PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();

	$result->pass=TRUE;
            $result->msg = "Welcome to Total Finance, $name!  "
                . "Please check your email to verify your new account. "
                . "An email was sent to $email.";
                return $result;
          
		} else {
		$result->pass = FALSE;
            $result->err_msg = "Error: "
                . "Couldn't insert the "
                . "user information into the database.";
            return $result;
		}      
	}

	// Changes the user's password

	public function updatePassword() {
		$result = new stdClass();
		$v = $_POST["v];
		$password = $_POST["password"];
		
		// if(isset($_POST['p']) && isset($_POST['r']) && $_POST['p']==$_POST['r']) {
		$sql = "UPDATE users
					SET hashedPW=MD5(:pass), verified=1
					WHERE ver_code=:ver
					LIMIT 1";
			try
			{
				$stmt = $this->_db->prepare($sql);
				$stmt->bindParam(":pass", $password, PDO::PARAM_STR);
				$stmt->bindParam(":ver", $v, PDO::PARAM_STR);
				$stmt->execute();
                $row = $stmt->fetch();
				$stmt->closeCursor();

                $result->pass = TRUE;	
                $result->msg = 'Your account password was validated. '
                            . 'Welcome to Total Finance! To '
                            . 'continue, please log in.';
                return $result;
			}
			catch(PDOException $e)
			{
                $result->pass = FALSE;
                $result->err_msg = 'Your password could not be set '
                              . 'at this time. Please try again later.';

				return $result;
			}
//		}
//		else
//		{
//			return FALSE;
//		}
	}
  
  
	/**
	 * Checks credentials and verifies a user account
	 * 
	 * @return array	an array containing a status code and status message
	 */
	public function verifyAccount()
	{
        $result = new stdClass();
        $email = $_POST['e'];
        $ver = $_POST['v'];

		$sql = "SELECT email, name
				FROM users
				WHERE ver_code=:ver
				AND SHA1(email)=:email
				AND verified=0";
      
		if($stmt = $this->_db->prepare($sql))
		{
			$stmt->bindParam(':ver', $ver, PDO::PARAM_STR);
			$stmt->bindParam(':email', $email, PDO::PARAM_STR);
			$stmt->execute();
			$row = $stmt->fetch();
            
            // DMZ question: is there a purpose to using this over rowCount?
            // TBD: maybe revise to return object for consistency betw php & js
            if(isset($row['email']))
			{
				// Logs the user in if verification is successful
//				$_SESSION['email'] = $row['email'];
//				$_SESSION['loggedIn'] = 1;
              
			}
			else
			{
                $result->pass = FALSE;
                $result->err_msg = "Verification Error: "
					. "This account has already been verified. "
					. "Did you forget "
					. "your password?";
				return $result;
			}
          
            $result->pass = TRUE;
            $result->user = $row;
          
            // DMZ question: what does this do?
			$stmt->closeCursor();

			return $result;
		}
		else
		{
            $result->pass = FALSE;
            $result->err_msg = "Database Error: "
                              . "Please try again later.";
			return $result;
		}
	}
  

	/**
	 * Sends an email to a user with a link to verify their new account
	 * 
	 * @param string $email	The user's email address
	 * @param string $ver	The random verification code for the user
	 * @return boolean		TRUE on successful send and FALSE on failure
	 */
	private function sendVerificationEmail($email, $name, $ver)
	{
		$e = sha1($email); // For verification purposes
		$to = trim($email);
	
		$subject = "[Total Finance] Please Verify Your Account";

		$headers = <<<MESSAGE
From: Total Finance Mailer <donotreply@pacifictech.us>
Content-Type: text/plain;
MESSAGE;

		$msg = <<<EMAIL
You have a new account at Total Finance!

To get started, please activate your account and choose a
password by following the link below.

Your Username: $name

Activate your account: http://localhost/test/index.php?v=$ver&e=$e

If you have any questions, please contact daniel@pacifictech.us .

--
Thanks!

Total Finance Team
TotalFinance.Herokuapp.com
EMAIL;

      //TBD: get mail fcn working & return something
//		return mail($to, $subject, $msg, $headers);
      // temp - so I can copy & paste url stuff for debugging
      return $msg;

//    public function experimentUpdate()
//	{
//		$sql = "UPDATE `users` SET `hashedPW`='aaa' WHERE `email`='jasonzz1@comcast.net'";
//
//        try
//	    {
//	    	$stmt = $this->_db->prepare($sql);
//	    	$stmt->execute();
//          echo $stmt->rowCount();
//	    	if($stmt->rowCount()==1)
//	    	{
////	    		$_SESSION['name'] = htmlentities($_POST['name'], ENT_QUOTES);
////	    		$_SESSION['LoggedIn'] = 1;
//	    		return TRUE;
//	    	}
//	    	else
//	    	{
//	    		return FALSE;
//	    	}
//	    }
//	    catch(PDOException $e)
//	    {
//	    	return FALSE;
//	    }
//	}
  
}
?>

