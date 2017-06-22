<?php

class WebConference {

  function __construct($server_url, $project_uuid) {
    $this->server_url = $server_url;
    $this->project_uuid = $project_uuid;
  }

  public function __get($property) {
    if (property_exists($this, $property)) {
      return $this->property;
    }
  }

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

  protected static function putHost($base_url, $project_uuid, $host_ID, $record_calls) {
    $host_ID = rawurlencode($host_ID);
    $host_data = false;
    if ($record_calls) {
      $host_data = array(
        "recordHostSessions" => ($record_calls == 1)
      );
      $host_data = json_encode($host_data);
    }
    return self::APIRestCall("PUT", "$base_url/host/$project_uuid/$host_ID", false, $host_data);
  }

  protected static function putAppointmentByProject($base_url, $project_uuid, $host_id,
                                                    $event_id, $appointment_data) {
    $host_id = rawurlencode($host_id);
    $event_id = rawurlencode($event_id);
    return self::APIRestCall("PUT",
                             "$base_url/appointment/$project_uuid/$host_id/$event_id",
                             false,
                             json_encode($appointment_data));
  }

  /**
   * Creates a host (room owner) URL on the web conferencing site and returns an object that
   * holds said URL. If $hostId is not defined, a new unique id will be generated. If $hostId
   * is defined, and it already exists on the web conferencing site, then it's existing URL will
   * be returned.
   * Param $name: Name of the room owner
   * Param $host_id: Unique identifier of the room owner).
   * Param $record_calls: Record all calls for this host.
   */
  public function getHostUrl($name, $host_id = false, $record_calls) {
    if (!$host_id) {
      $host_id = uniqid('', true);
    }
    $host_id = $host_id . '||' . $name;
    $result = self::putHost($this->server_url, $this->project_uuid, $host_id, $record_calls);
    $result = json_decode($result);
    $result->host_id = $host_id;
    $result->url = $this->server_url . $result->url;
    return $result;
  }

  /**
   * Schedules a meeting (starting now) on the web conferencing site, associated to the the $hostId
   * meeting room with $guestId as the guest. The goal of the meeting will be set to the $description
   * parameter, the starting time will be set to 'now' and  the meeting duration to 120 minutes.
   * It returns an object that holds the URL of the newly created meeting. The guest can load this
   * URL to access the meeting waiting room.
   * Param $host_id: Identifier of the existing room owner for whom we want to schedule the meeting
   * Param $guest_id: Identifier of the guest that we want to schedule a meeting with
   * Param $guest_name: Name of the guest
   * Param $description: Description of the goal of the meeting
   */
  public function getAppointmentUrl($host_id, $guest_id, $guest_name, $description) {
    $guest_id = $guest_id . '||' . $guest_name;
    $descrip = $description;
    $now = time();
    $duration = 120 * 60;
    $not_after = $now + $duration * 2;
    $start_time = $now + $duration / 2;
    $end_time = $not_after - $duration / 2;
    $appointment_data = array(
      "guestId" => $guest_id,
      "description" => $descrip,
      "allowRecording" => false,
      "notBefore" => $now,
      "notAfter" => $not_after,
      "startTime" => $start_time,
      "endTime" => $end_time
    );
    $result = self::putAppointmentByProject($this->server_url,
                                            $this->project_uuid,
                                            $host_id,
                                            uniqid('', true),
                                            $appointment_data);
    $result = json_decode($result);
    $result->url = $this->server_url . $result->url;
    return $result;
  }
}
?>
