<?php

class API_Authentication
{
    private $mysqli_con;
    private $db_host = "localhost";
    private $db_user = "root";
    private $db_pass = "";
    private $db_name = "api_test";

    public function __construct()
    {
        try {
            $this->mysqli_con = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name);

            if ($this->mysqli_con->connect_errno) {
                throw new Exception("Connection failed: " . $this->mysqli_con->connect_error);
            }
        } catch (Exception $e) {
            die("<b>Failed:</b> Connection Issue!");
        }
    }

    public function authenticateToken()
    {
        // Check if the database connection is available
        if (!$this->mysqli_con && !$this->mysqli_con->ping()) {
            die("Failded to Connect.");
        }

        // Check if the request method is GET
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['token'])) {
            $token = $this->sanitizeToken($_GET['token']);

            // Retrieve the stored token from the Namecheap server securely
            $storedToken = "123234"; // Replace with your actual stored token

            try {
                if ($this->validateToken($token, $storedToken)) {
                    // Token is valid, process the API request
                    $data = file_get_contents('secret_api.json'); // Read the content of secret_api.json
                    if ($data === false) {
                        throw new Exception("Failed to read data file.");
                    }
                    $responseData = json_decode($data, true); // Decode the JSON data
                    if ($responseData === null) {
                        throw new Exception("Failed to parse JSON data.");
                    }

                    // Return the API response
                    header('Content-Type: application/json');
                    echo json_encode($responseData);
                } else {
                    throw new Exception("Token Invalid or Expired");
                }
            } catch (Exception $e) {
                // Token is invalid or an error occurred, deny the request
                http_response_code(401); // Unauthorized status code
                echo "Unauthorized: " . $e->getMessage();
            }
        } else {
            // Handle the case when the request method is not GET or token is not present
            http_response_code(405); // Method Not Allowed status code
            echo "Invalid Approach";
        }
    }

    private function validateToken($token, $storedToken)
    {
        $token = $this->sanitizeToken($token);
        $storedToken = $this->sanitizeToken($storedToken);

        return ($token === $storedToken);
    }

    private function sanitizeToken($token)
    {
        $token = trim($token);
        $token = mysqli_real_escape_string($this->mysqli_con, $token);
        return $token;
    }

    public function __destruct()
    {
        $this->mysqli_con->close();
    }
}

$api_authentication = new API_Authentication();
$api_authentication->authenticateToken();
