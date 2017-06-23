<?php
include_once('error_log.php');

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
    $request = new WP_Http;
    $headers = null;

    $params = array(
      'method' => $method
    );

    if ($data) {
      $params['headers'] = array(
        'Content-Type' => 'application/json'
      );
      $params['body'] = $data;
    }

    if ($query) {
      $url = sprintf("%s?%s", $url, http_build_query($query));
    }

    $result = $request->request($url, $params);
    if (is_a($result, 'WP_Error')) {
      write_log($result);
      $result = [
        "body" => [
          "url" => null
        ]
      ];
    }

    return $result['body'];
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
