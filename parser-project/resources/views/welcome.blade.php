<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>parser</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <style>
            html, body {
                background-color: #fff;
                color: #636b6f;
                font-family: 'Nunito', sans-serif;
                font-weight: 200;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 13px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
<p>
    Upload a text file:
</p>
    <div>
        <form action="/" method="post" enctype="multipart/form-data">
            <input type="file" name="email">
            <input type="submit" name="submit">
            <input type="submit" name="download" value="download html">
        </form>
        <pre>
        <?php

            // Email data set that I know about
            $emailNotes = [
                "\nReceived:",
                "\nFrom:",
                "\nTo:",
                "\nSubject:",
                "\nDate:",
                "\nMessage-ID:",
                "\nAcount no.:",
                "\nMIME-Version:",
                "\nMime-Version:",
                "\nX-Ninja-Mailer-ID:",
                "\nX-LibVersion:",
                "\nList-Unsubscribe:",
                "\nx-idmail:",
                "\nReply-To:",
                "\nList-Unsubscribe:",
                "\nContent-Type:",
                "\nReturn-Path:",
                "\nX-Original-To:",
                "\nDelivered-To:",
                "\nContent-Transfer-Encoding:",
                "\nX-BBounce:",
                "\nX-campaignid:" ,
                "\nX-Vitals:"
            ];

            // This array is the values to display to the end user
            $displayValues = [
                "\nTo:",
                "\nFrom:",
                "\nDate:",
                "\nSubject:",
                "\nMessage-ID:"
            ];

        /**
         * finds the needle in the haystack and returns the string the occurs between the needle and an
         * array of potential end needles
         *
         * @param $Needle
         * @param $Haystack
         * @param array $emailNotes
         * @return string
         */
            function findNeedle($Needle, $Haystack, $emailNotes = ["\nX-Vitals:", "\nMime-Version:", "\nReceived:","\nFrom:","\nTo:","\nSubject:","\nDate:","\nMessage-ID:","\nAcount no.:","\nMIME-Version:","\nX-Ninja-Mailer-ID:","\nX-LibVersion:","\nList-Unsubscribe:","\nx-idmail:","\nReply-To:","\nList-Unsubscribe:","\nContent-Type:","\nReturn-Path:","\nX-Original-To:","\nDelivered-To:","\nContent-Transfer-Encoding:","\nX-BBounce:", "\nX-campaignid:"]){
                $explodedValue = explode($Needle, $Haystack);


                if (empty($explodedValue[1])) {
                    return "No Value";
                }

                $startOfNeedle = $explodedValue[1];

                foreach ($emailNotes as $key => $value) {
                    $startOfNeedle = explode($value, $startOfNeedle)[0];
                }
                return $startOfNeedle;

            }

        /**
         * finds all word locations in the string and returns the
         * LAST letter's location as part of an array.
         *
         * @param $word
         * @param $string
         * @param $array
         * @return mixed
         */
            function findWords($word, $string, $array)
                {
                    try {
                        $lastPosition = 0;
                        while (($lastPosition = strpos($string, $word, $lastPosition))!== false) {
                            $lastPosition = $lastPosition + strlen($word);
                            $array[$lastPosition] = $word;
                        }

                        return $array;
                    } catch (\Exception $e){
                        return $array;
                    }

                }

            // The main function that will divide up the emails and
            //return the views to the user.

            if ((!empty($_POST['submit'])||!empty($_POST['download'])) && !empty($_FILES['email']['tmp_name'])) {

                // gets the file
                $file = file_get_contents($_FILES['email']['tmp_name']);

                // breaks up the email "Body" vs. the email headers
                $file = str_replace("</html>", "<html>", $file);
                $file = str_replace("<tbody>", "<html>", $file);
                $file = str_replace("</tbody>", "<html>", $file);
                $file = str_replace('<!DOCTYPE', "<html>", $file);
                $file = str_replace("<html", "<html>", $file);



                // This breaks up all emails with a boundary code instead of html
                $boundaryCodeLocationsArray = findWords("boundary=\"", $file, []);
                $tempFile = $file;

                $boundaryCodeArray = [];
                foreach ($boundaryCodeLocationsArray as $boundaryCodeLocation => $boundaryCodeStart) {
                    $boundaryCodeArray[] = findNeedle($boundaryCodeStart, $tempFile, ["\""]);

                    // removes any previous data before the subject
                    $trimmedSectionOfEmail = explode($boundaryCodeStart, $tempFile, 2);

                    // just in case the subject is not in the value
                    if (empty($trimmedSectionOfEmail[1])) {
                        $tempFile = $trimmedSectionOfEmail[0];
                    } else {
                        $tempFile = $trimmedSectionOfEmail[1];
                    }
                }

                foreach ($boundaryCodeArray as $boundaryCode) {
                    $file = str_replace("--".$boundaryCode, "<html>", $file);
                }

                $emailArrays= explode('<html>', $file);

                // goes through each section of the email
                foreach ($emailArrays as $key => $sectionOfEmail) {

                    // this if statement is an attempt to discern the header of the
                    // email from the body of the array
                    if (strpos($sectionOfEmail, "X-Original-To:")) {
                        $headersArray = [];

                        // this sorts the array of various email header subjects
                        foreach ($emailNotes as $subjectHeader) {
                            $headersArray = findWords($subjectHeader, $sectionOfEmail, $headersArray);
                        }
                        ksort($headersArray);


                        foreach ($headersArray as $subjectHeader) {

                            // returns the value of the subject name in the header
                            $ToNeedle = findNeedle($subjectHeader, $sectionOfEmail);

                            // removes any previous data before the subject
                            $trimmedSectionOfEmail = explode($subjectHeader, $sectionOfEmail, 2);

                            // just in case the subject is not in the value
                            if (empty($trimmedSectionOfEmail[1])) {
                                $sectionOfEmail = $trimmedSectionOfEmail[0];
                            } else {
                                $sectionOfEmail = $trimmedSectionOfEmail[1];
                            }

                            // This is where you can store the value of the email.
                            // I went and displayed it because It was me doing something with it
                            if (in_array($subjectHeader, $displayValues)) {
                                // echoing out the value
                                echo $subjectHeader . " " . str_replace("<", "&lt;", $ToNeedle) ;
                            }
                        }

                        echo "<br/>";
                    }
                }


                // so the user can download the html to be used later
                if (!empty($_POST['download'])) {
                    header("Content-type: text/plain");
                    header('Content-Disposition: attachment; filename="Parsed Email"');
                }

            }
        ?>
</pre>
    </div>
    </body>
</html>
