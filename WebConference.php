<?php

class WebConference {
    /**
     * Method: POST, PUT, GET etc
     * Query: array("param" => "value") ==> index.php?param=value. That is, what goes on the
     * query string
     */
    protected static function APIRestCall($method, $url, $query = false, $data = false) {
        $curl = curl_init();
        switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            break;
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
        default:
           curls_setopt($curl, CURLOPT_GET, 1);
        }
        if ($query) {
           $url = sprintf("%s?%s", $url, http_build_query($query));
        }
        if ($data) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json'
            ));
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }

    protected static function putHost($baseURL, $projectUUID, $hostID, $recordCalls) {
      $hostID = rawurlencode($hostID);
      $hostData = false;
      if ($recordCalls) {
        $hostData = array(
          "recordHostSessions" => ($recordCalls == 1)
        );
        $hostData = json_encode($hostData);
      }
      return self::APIRestCall("PUT", "$baseURL/host/$projectUUID/$hostID", false, $hostData);
    }

    protected static function putAppointmentByProject($baseURL, $projectUUID, $hostId,
                                                      $eventId, $appointmentData) {
      $hostId = rawurlencode($hostId);
      $eventId = rawurlencode($eventId);
      return self::APIRestCall("PUT",
                               "$baseURL/appointment/$projectUUID/$hostId/$eventId",
                               false,
                               json_encode($appointmentData));
    }

    /**
     * Creates a host (room owner) URL on the web conferencing site and returns an object that
     * holds said URL. If $hostId is not defined, a new unique id will be generated. If $hostId
     * is defined, and it already exists on the web conferencing site, then it's existing URL will
     * be returned.
     * Param $name: Name of the room owner
     * Param $hostId: Unique identifier of the room owner).
     */
    public static function getHostUrl($name, $hostId = false, $recordCalls) {
        if (!$hostId) {
            $hostId = uniqid('', true);
        }
        $hostId = $hostId . '||' . $name;
        $result = self::putHost(WEBCONFERENCE_SERVER_ORIGIN, WEBCONFERENCE_PROJECT_UUID, $hostId, $recordCalls);
        $result = json_decode($result);
        $result->hostId = $hostId;
        $result->url = WEBCONFERENCE_SERVER_ORIGIN . $result->url;
        return $result;
    }

    /**
     * Schedules a meeting (starting now) on the web conferencing site, associated to the the $hostId
     * meeting room with $guestId as the guest. The goal of the meeting will be set to the $description
     * parameter, the starting time will be set to 'now' and  the meeting duration to 120 minutes.
     * It returns an object that holds the URL of the newly created meeting. The guest can load this
     * URL to access the meeting waiting room.
     * Param $hostId: Identifier of the existing room owner for whom we want to schedule the meeting
     * Param $guestId: Identifier of the guest that we want to schedule a meeting with
     * Param $guestName: Name of the guest
     * Param $description: Description of the goal of the meeting
     */
    public static function getAppointmentUrl($hostId, $guestId, $guestName, $description) {
        $guestId = $guestId . '||' . $guestName;
        $descrip = $description;
        $now = time();
        $duration = 120 * 60;
        $notAfter = $now + $duration * 2;
        $startTime = $now + $duration / 2;
        $endTime = $notAfter - $duration / 2;
        $appointmentData = array(
            "guestId" => $guestId,
            "description" => $descrip,
            "allowRecording" => false,
            "notBefore" => $now,
            "notAfter" => $notAfter,
            "startTime" => $startTime,
            "endTime" => $endTime
        );
        $result = self::putAppointmentByProject(WEBCONFERENCE_SERVER_ORIGIN,
                                                WEBCONFERENCE_PROJECT_UUID,
                                                $hostId,
                                                uniqid('', true),
                                                $appointmentData);
        $result = json_decode($result);
        $result->url = WEBCONFERENCE_SERVER_ORIGIN . $result->url;
        return $result;
    }
}
