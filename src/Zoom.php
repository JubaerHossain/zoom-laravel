<?php
namespace Jubaer\Zoom;

use GuzzleHttp\Client;

class Zoom
{
    protected string $accessToken;
    protected $client;
    protected $account_id;
    protected $client_id;
    protected $client_secret;

    public function __construct()
    {

        $this->client_id = auth()->user()->zoomSetting->client_id ?? config('zoom.client_id');
        $this->client_secret = auth()->user()->zoomSetting->client_secret ?? config('zoom.client_secret');
        $this->account_id = auth()->user()->zoomSetting->account_id ?? config('zoom.account_id');
        $this->accessToken = $this->getAccessToken();

        $this->client = new Client([
            'base_uri' => 'https://api.zoom.us/v2/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    protected function getAccessToken()
    {

        $client = new Client([
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Host' => 'zoom.us',
            ],
        ]);

        $response = $client->request('POST', "https://zoom.us/oauth/token", [
            'form_params' => [
                'grant_type' => 'account_credentials',
                'account_id' => $this->account_id,
            ],
        ]);

        $responseBody = json_decode($response->getBody(), true);
        return $responseBody['access_token'];
    }

    public function createMeeting(array $data)
    {
        try {
            $response = $this->client->request('POST', 'users/me/meetings', [
                'json' => $data,
            ]);
            $res = json_decode($response->getBody(), true);
            return [
                'status' => true,
                'data' => $res,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function getMeeting(string $meetingId)
    {
        try {
            $response = $this->client->request('GET', 'meetings/' . $meetingId);
            $data = json_decode($response->getBody(), true);
            return [
                'status' => true,
                'data' => $data,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function getAllMeeting()
    {
        $response = $this->client->request('GET', 'users/me/meetings');

        return json_decode($response->getBody(), true);
    }

    public function getUpcomingMeeting()
    {
        $response = $this->client->request('GET', 'users/me/meetings?type=upcoming');

        return json_decode($response->getBody(), true);
    }

    public function getPreviousMeetings()
    {
        $meetings = $this->getAllMeeting();

        $previousMeetings = [];

        foreach ($meetings['meetings'] as $meeting) {
            $start_time = strtotime($meeting['start_time']);

            if ($start_time < time()) {
                $previousMeetings[] = $meeting;
            }
        }

        return $previousMeetings;
    }

    public function rescheduleMeeting(string $meetingId, array $data)
    {
        $response = $this->client->request('PATCH', 'meetings/' . $meetingId, [
            'json' => $data,
        ]);

        return json_decode($response->getBody(), true);
    }

    public function endMeeting($meetingId)
    {
        $response = $this->client->request('PUT', 'meetings/' . $meetingId . '/status', [
            'json' => [
                'action' => 'end',
            ],
        ]);

        return $response->getStatusCode() === 204;
    }

    public function deleteMeeting(string $meetingId)
    {
        try {
            $response = $this->client->request('DELETE', 'meetings/' . $meetingId);
            if ($response->getStatusCode() === 204) {
                return [
                    'status' => true,
                    'message' => 'Meeting Deleted Successfully',
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Something went wrong',
                ];
            }
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }

    }

    public function recoverMeeting($meetingId)
    {
        $response = $this->client->request('PUT', 'meetings/' . $meetingId . '/status', [
            'json' => [
                'action' => 'recover',
            ],
        ]);

        return $response->getStatusCode() === 204;
    }

    public function getUsers($data)
    {
        try {
            $response = $this->client->request('GET', 'users', [
                'query' => [
                    'page_size' => @$data['page_size'] ?? 300,
                    'status' => @$data['status'] ?? 'active',
                    'page_number' => @$data['page_number'] ?? 1,
                ],
            ]);
            $responseData = json_decode($response->getBody(), true);
            $data = [];
            $data['current_page'] = $responseData['page_number'];
            $data['profile'] = $responseData['users'][0];
            $data['last_page'] = $responseData['page_count'];
            $data['per_page'] = $responseData['page_size'];
            $data['total'] = $responseData['total_records'];
            return [
                'status' => true,
                'data' => $data,
            ];
        } catch (\Throwable $th) {
            return [
                'status' => false,
                'message' => $th->getMessage(),
            ];
        }

    }
}
