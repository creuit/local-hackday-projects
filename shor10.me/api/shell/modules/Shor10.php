<?php

// Trait that handles HTTP2 pushing of certain assets
trait Shor10 {
    // Inserts a new long URL in the database and returns the short url
    function insertURL($short, $long) {
        // Escape the inserted long URL to combat mysql injections
        $long = $this->db->real_escape_string($long);
        // Format the domain string (e.g. https://www.example.com)
        $domain = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'];
        // Prepare the SQL query
        $sql = "SELECT short_url
                FROM urls
                WHERE long_url = '$long';
        ";
        $existing_short = $this->db->query($sql);
        // The URL has already been shortened! Return it.
        if ($existing_short) {
            $short = $existing_short;
        }
        else {
            // Long URL doesn't exist in database, let's insert it!
            $sql = "INSERT INTO urls (short_url, long_url)
                    VALUES
                    ('$short', '$long');
            ";
            $result = $this->db->query($sql);
            if (!$result) {
                // Couldn't insert the id! Report error.
                $this->response("LINK_CREATION_FAILED");
            }
        }
        // Format the domain string (e.g. https://www.example.com)
        $domain = $_SERVER['SERVER_NAME'];
        // Format the final redirection URL
        $short_link = "$domain/$short";
        // Return it to the frontend
        $this->response("SUCCESS", $short_link);
    }
    // Handles redirection if the page requested is a short URL
    function handleRedirect() {
        // Get the request URI
        $url = $_SERVER['REQUEST_URI'];
        // If it's length is 5 (slash inclusive, e.g. '/abcd')
        if (strlen($url) == 5) {
            // Get the last 4 chars
            $short = substr($url, -4);
            // Make sure the shortlink only contains our valid characters!
            if ($this->validatePattern("short", $short)) {
                $sql = "SELECT long_url
                        FROM urls
                        WHERE short_url = '$short';
                ";
                // Get the long URL of this shortlink
                //   If it is false, then the site will display
                //   a 404 error when rendering since the request URI doesn't
                //   belong to a valid page in the site
                $long = $this->db->query($sql);
                if ($long) {
                    // Redirect to the long URL
                    header("Location: " . $long);
                    die();
                }
            }
        }
    }

}
