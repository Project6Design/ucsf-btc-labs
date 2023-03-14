<?php

namespace Drupal\ik_constant_contact\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;


/**
 * Class ConstantContact.
 *
 * Class to handle API calls to Constant Contact.
 */
class ConstantContact {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Cache\CacheBackendInterface.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   *   Drupal cache.
   */
  protected $cache;

  /**
   * Drupal\Core\Config\ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   *   Drupal config.
   */
  protected $config;

  /**
   * Drupal\Core\Session\AccountProxy.
   *
   * @var \Drupal\Core\Session\AccountProxy
   *   Drupal current user.
   */
  protected $currentUser;

  /**
   * Drupal\Core\Logger\LoggerChannelFactoryInterface.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   *   Drupal logging.
   */
  protected $loggerFactory;

  /**
   * Drupal\Core\Messenger\MessengerInterface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   *   Messenger Interface.
   */
  protected $messenger;

  /**
   * Drupal\Core\Site\Settings.
   *
   * @var \Drupal\Core\Site\Settings
   *   Drupal site settings.
   */
  protected $settings;

  /**
   * GuzzleHttp\Client.
   *
   * @var \GuzzleHttp\Client
   *   Guzzle HTTP Client.
   */
  protected $httpClient;

  /**
   * \Drupal\Core\Extension\ModuleHandlerInterface
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   *   Module handler interface
   */
  protected $moduleHandler;

  /**
   * The Constant Contact v3 API endpoint.
   *
   * @var string
   */
  protected $apiUrl = 'https://api.cc.email/v3';

  /**
   * The URL to use for authorization.
   *
   * @var string
   */
  protected $authUrl = 'https://authz.constantcontact.com/oauth2/default/v1/authorize';

  /**
   * The URL to use for token oauth.
   *
   * @var string
   */
  protected $tokenUrl = 'https://authz.constantcontact.com/oauth2/default/v1/token';

  /**
   * Constructs the class.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The interface for cache implementations.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The factory for logging channels.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The runtime messages sent out to individual users on the page.
   * @param \Drupal\Core\Site\Settings $settings
   *   The read settings that are initialized with the class.
   * @param \GuzzleHttp\Client $httpClient
   *   The client for sending HTTP requests.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(CacheBackendInterface $cache, ConfigFactory $config, AccountProxy $currentUser, LoggerChannelFactoryInterface $loggerFactory, MessengerInterface $messenger, Settings $settings, Client $httpClient, ModuleHandlerInterface $moduleHandler) {
    $this->cache = $cache;
    $this->config = $config;
    $this->currentUser = $currentUser;
    $this->loggerFactory = $loggerFactory->get('ik_constant_contact');
    $this->messenger = $messenger;
    $this->settings = $settings;
    $this->httpClient = $httpClient;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * Returns the configurations for the class.
   *
   * @return array
   *   Returns an array with all configuration settings.
   */
  public function getConfig() {
    // Get our settings from settings.php.
    $settings = $this->settings::get('ik_constant_contact');
    $clientId = isset($settings['client_id']) ? $settings['client_id'] : NULL;
    $secret = isset($settings['client_secret']) ? $settings['client_secret'] : NULL;
    $authType = isset($settings['auth_type']) ? $settings['auth_type'] : NULL;
    $configType = 'settings.php';

    // If nothing is in settings.php, let's check our config files.
    if (!$settings) {
      $clientId = $this->config->get('ik_constant_contact.config')->get('client_id');
      $secret = $this->config->get('ik_constant_contact.config')->get('client_secret');
      $authType = $this->config->get('ik_constant_contact.config')->get('auth_type');
      $configType = 'config';
    }

    $code_verifier = $this->config->get('ik_constant_contact.pkce')->get('code_verifier');

    if (!$this->moduleHandler->moduleExists('automated_cron') && !$this->moduleHandler->moduleExists('ultimate_cron') && (int)$this->currentUser->id() !== 0 && $this->currentUser->hasPermission('administer constant contact configuration')) {
      $this->messenger->addMessage($this->t('It is recommended to install automated_cron or make sure that cron is run regularly to refresh access tokens from Constant Contact API.'), 'warning');
    }

    return [
      'client_id' => $clientId,
      'client_secret' => $secret,
      'auth_type' => $authType,
      'config_type' => $configType, // Application client_id and other info found in settings.php or via config
      'access_token' => $this->config->get('ik_constant_contact.tokens')->get('access_token'),
      'refresh_token' => $this->config->get('ik_constant_contact.tokens')->get('refresh_token'),
      'code_verifier' => $code_verifier,
      'authentication_url' => $this->authUrl,
      'token_url' => $this->tokenUrl,
      'contact_url' => $this->apiUrl . '/contacts',
      'contact_lists_url' => $this->apiUrl . '/contact_lists',
      'campaigns_url' => $this->apiUrl . '/emails',
      'campaign_activity_url' => $this->apiUrl . '/emails/activities',

      // Add fields to configuration for signup form block configuration
      // @see https://v3.developer.constantcontact.com/api_guide/contacts_create_or_update.html#method-request-body
      'fields' => [
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'company_name' => 'Company',
        'job_title' => 'Job Title',
        'street_address' => 'Address',
        'phone_number' => 'Phone Number',
        'birthday' => 'Birthday',
        'anniversary' => 'Anniversary',
      ],
      'address_subfields' => [
        'street' => 'Street',
        'city' => 'City',
        'state' => 'State',
        'postal_code' => 'Postal Code',
        'country' => 'Country',
      ],
    ];
  }

  public function base64UrlEncode($string) {
    $base64 = base64_encode($string);
    $base64 = trim($base64, '=');
    $base64url = strtr($base64, '+/', '-_');

    return $base64url;
  }

  /**
   * Generates a random PKCE Code Verifier.
   *
   * https://datatracker.ietf.org/doc/html/rfc7636#section-4.1
   * https://v3.developer.constantcontact.com/api_guide/pkce_flow.html#generate-the-code-verifier
   *
   * @return string
   */
  public function generateCodeVerifier() {
    $random = bin2hex(openssl_random_pseudo_bytes(32));
    $verifier = $this->base64UrlEncode(pack('H*', $random));

    return $verifier;
  }

  /**
   * Return the encoded code challenge for the PKCE Code Verifier to send to API Authorization Endpoint.
   *
   * https://v3.developer.constantcontact.com/api_guide/pkce_flow.html#generate-the-code-challenge
   *
   * @param string $codeVerifier
   * @return string
   */
  public function getCodeChallenge($codeVerifier) {
    return $this->base64UrlEncode(pack('H*', hash('sha256', $codeVerifier)));
  }

  /**
   * Shared method to generate the rest of the request body.
   * 
   * @NOTE that email_address, permission_to_send are not added hear since the fields are
   * different per api call type. For example, the list_memberships, the email_address field. 
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/contacts_create_or_update.html#method-request-body
   *
   * @param array $data - posted data from our form
   * @param object $body - An object already generated.
   * @return object $body
   */
  protected function buildResponseBody(array $data, object $body) {
    $fields = $this->getConfig()['fields'];

    foreach ($fields as $field => $name) {
      if (isset($data[$field]) && $data[$field]) {
        if ($field === 'birthday') {
          if (isset($data[$field]['month']) && $data[$field]['month'] && isset($data[$field]['day']) && $data[$field]['day']) {
            $body->birthday_month = (int)$data[$field]['month'];
            $body->birthday_day = (int)$data[$field]['day'];
          }
        } else if ($field === 'street_address') {
          $body->{$field} = (object)$data[$field];
        } else {
          $body->{$field} = $data[$field];
        }
      }
    }

    if (isset($data['custom_fields']) && count($data['custom_fields']) > 0) {
      foreach ($data['custom_fields'] as $id => $value) {
        $body->custom_fields[] = ['custom_field_id' => $id, 'value' => $value];
      }
    }

    return $body;
  }

  /**
   * Creates a new contact by posting to Constant Contact API.
   *
   * @param array $data
   *   Array of data to send to Constant Contact.
   *    Requires 'email_address' key.
   *    Can also accept 'first_name' and 'last_name'.
   * @param array $listIDs
   *   An array of list UUIDs where we want to add this contact.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/createContact
   */
  private function createContact(array $data, $listIDs) {
    $config = $this->getConfig();

    $body = (object) [
      'email_address' => (object) [
        'address' => NULL,
        'permission_to_send' => NULL,
      ],
      'first_name' => NULL,
      'last_name' => NULL,
      'create_source' => NULL,
      'list_memberships' => NULL,
    ];

    $body = $this->buildResponseBody($data, $body);

    // Add our required fields.
    $body->email_address->address = $data['email_address'];
    $body->email_address->permission_to_send = 'implicit';
    $body->list_memberships = $listIDs;
    $body->create_source = 'Account';

    $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
    $this->moduleHandler->invokeAll('ik_constant_contact_contact_create_data_alter', [$data, &$body]);

    try {
      $response = $this->httpClient->request('POST', $config['contact_url'], [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
        'body' => json_encode($body),
      ]);

      $this->handleResponse($response, 'createContact');
    }
    catch (RequestException $e) {
      // Return the error to show an error on form submission
      return $this->handleRequestException($e);
    }
    catch (ClientException $e) {
      $this->handleRequestException($e);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }


  /**
   * Fetch the details of a single campaign.
   *
   * @param string $id
   *   The id of the campaign.
   *
   * @return mixed
   *   An stdClass of the campaign.
   * @throws \GuzzleHttp\Exception\GuzzleException
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/email_campaign_id.html
   */
  public function getCampaign(string $id) {
    $config = $this->getConfig();
    try {
      $response = $this->httpClient->request('GET', $config['campaigns_url'] . '/' . $id, [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $this->updateTokenExpiration();
      $json = json_decode($response->getBody()->getContents());
      return $json;
    }
    catch (RequestException $e) {
      $this->handleRequestException($e);
    }
    catch (ClientException $e) {
      $this->handleRequestException($e);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);
    }
  }

  /**
   * Get the campaign activity details by id.
   *
   * @param string $id
   *   The id of the activity.
   *
   * @return mixed
   *   A stdClass of the activity.
   * @throws \GuzzleHttp\Exception\GuzzleException
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/email_campaigns_activity_id.html
   */
  public function getCampaignActivity(string $id) {
    $config = $this->getConfig();
    $url = $config['campaign_activity_url'] . '/' . $id;
    $url .= '?include=permalink_url';
    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $this->updateTokenExpiration();
      $json = json_decode($response->getBody()->getContents());
      return $json;
    }
    catch (RequestException $e) {
      $this->handleRequestException($e);
    }
    catch (ClientException $e) {
      $this->handleRequestException($e);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);
    }
  }

  /**
   * Returns a list of the campaigns.
   *
   * @param array $status
   *   An option to filter campaigns by status.
   *
   * @return array
   *   An array of campaigns.
   * @throws \GuzzleHttp\Exception\GuzzleException
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/email_campaigns_collection.html
   */
  public function getCampaigns($status = []) {
    $config = $this->getConfig();
    try {
      $response = $this->httpClient->request('GET', $config['campaigns_url'], [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $this->updateTokenExpiration();
      $json = json_decode($response->getBody()->getContents());
      $list = [];

      foreach ($json->campaigns as $campaign) {
        if (!empty($status) && in_array($campaign->current_status, $status)) {
          $list[] = $campaign;
        }
        else if (empty($status)) {
          $list[] = $campaign;
        }

      }
      return $list;
    }
    catch (RequestException $e) {
      $this->handleRequestException($e);
    }
    catch (ClientException $e) {
      $this->handleRequestException($e);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);
    }
  }

  /**
   * Checks if a contact exists already.
   *
   * @param array $data
   *   Array of data to send. 'email_address' key is required.
   *
   * @return array
   *   Returns a json response that determines if a contact
   *   already exists or was deleted from the list.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/getContact
   */
  private function getContact(array $data) {
    $config = $this->getConfig();

    try {
      $response = $this->httpClient->request('GET', $config['contact_url'] . '?email=' . $data['email_address'], [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
      ]);

      $this->updateTokenExpiration();
      $json = json_decode($response->getBody()->getContents());

      if ($json->contacts) {
        return $json;
      }
      else {
        return $this->getDeleted($this->apiUrl . '/contacts?status=deleted&include_count=TRUE', $data['email_address']);
      }
    }
    catch (RequestException $e) {
      // Return the error to show an error on form submission
      return $this->handleRequestException($e);
    }
    catch (ClientException $e) {
      $this->handleRequestException($e);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }

  /**
   * Gets contact lists from Constant Contact API.
   * 
   * @param $cached 
   *  Whether to return a cached response or not. 
   *  @see https://www.drupal.org/project/ik_constant_contact/issues/3282088 and https://v3.developer.constantcontact.com/api_guide/faqs_manage_applications.html
   *  Cron run perhaps was calling Drupal cached version which may have allowed refresh tokens to expire.
   *
   * @return array
   *   Returns an array of lists that the account has access to.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contact_Lists/getLists
   * 
   */
  public function getContactLists($cached = true) {
    $config = $this->getConfig();
    $cid = 'ik_constant_contact.lists';
    $cache = ($cached === true ? $this->cache->get($cid) : null);

    if ($cache && $cache->data && count($cache->data) > 0) {
      return $cache->data;
    }
    else {
      // Update access tokens.
      $this->refreshToken(false);

      if (isset($config['access_token'])) {
        try {
          $response = $this->httpClient->request('GET', $config['contact_lists_url'], [
            'headers' => [
              'Authorization' => 'Bearer ' . $config['access_token'],
              'cache-control' => 'no-cache',
              'content-type' => 'application/json',
              'accept' => 'application/json',
            ],
          ]);

          $this->updateTokenExpiration();
          $json = json_decode($response->getBody()->getContents());
          $lists = [];

          if ($json->lists) {
            foreach ($json->lists as $list) {
              $lists[$list->list_id] = $list;
            }

            $this->saveContactLists($lists);
            return $lists;
          }
          else {
            $this->messenger->addMessage($this->t('There was a problem getting your available contact lists.'), 'error');
          }
        }
        catch (RequestException $e) {
          $this->handleRequestException($e);
          $this->messenger->addMessage($this->t('There was a problem getting your available contact lists.'), 'error');
        }
        catch (ClientException $e) {
          $this->handleRequestException($e);
          $this->messenger->addMessage($this->t('There was a problem getting your available contact lists.'), 'error');
        }
        catch (\Exception $e) {
          $this->loggerFactory->error($e);
          $this->messenger->addMessage($this->t('There was a problem getting your available contact lists.'), 'error');
        }
      }
      else {
        return [];
      }
    }
  }

  /**
   * Returns custom fields available
   * 
   * @param $cached 
   *  Whether to return a cached response or not. 
   * 
   * @return mixed
   *   A stdClass of custom fields.
   * 
   * @see https://v3.developer.constantcontact.com/api_guide/get_custom_fields.html
   */
  public function getCustomFields($cached = true) {
    $config = $this->getConfig();
    $cid = 'ik_constant_contact.custom_fields';
    $cache = ($cached === true ? $this->cache->get($cid) : null);

    if ($cache && !is_null($cache) && $cache->data && property_exists($cache->data, 'custom_fields')) {
      return $cache->data;
    }
    else {
    
      $url = $this->apiUrl . '/contact_custom_fields';

      try {
        $response = $this->httpClient->request('GET', $url, [
          'headers' => [
            'Authorization' => 'Bearer ' . $config['access_token'],
            'cache-control' => 'no-cache',
            'content-type' => 'application/json',
            'accept' => 'application/json',
          ],
        ]);

        $this->updateTokenExpiration();
        $json = json_decode($response->getBody()->getContents());

        $this->cache->set($cid, $json);

        return $json;
      }
      catch (RequestException $e) {
        $this->handleRequestException($e);
      }
      catch (ClientException $e) {
        $this->handleRequestException($e);
      }
      catch (\Exception $e) {
        $this->loggerFactory->error($e);
      }
    }
  }

  /**
   * Checks if a contact is deleted from a list.
   *
   * This loops through all the deleted contacts of a
   * list and returns if there is a match to the email address.
   *
   * @param string $endpoint
   *   The endpoint to check. @see $this->getContact()
   * @param string $email
   *   The email address we're looking for.
   *
   * @return array
   *   Returns an array of a matched deleted contact.
   *
   * @see https://community.constantcontact.com/t5/Developer-Support-ask-questions/API-v-3-409-conflict-on-POST-create-a-Contact-User-doesn-t/td-p/327518
   */
  private function getDeleted($endpoint, $email) {
    $config = $this->getConfig();

    $deleted = $this->httpClient->request('GET', $endpoint, [
      'headers' => [
        'Authorization' => 'Bearer ' . $config['access_token'],
        'cache-control' => 'no-cache',
        'content-type' => 'application/json',
        'accept' => 'application/json',
      ],
    ]);

    $deleted = json_decode($deleted->getBody()->getContents());
    $match = NULL;

    if (count($deleted->contacts)) {
      foreach ($deleted->contacts as $value) {
        if ($value->email_address->address === $email) {
          $match = $value;
        }
      }
    }

    if (!$match &&  property_exists($deleted, '_links') && property_exists($deleted->_links, 'next') && property_exists($deleted->_links->next, 'href')) {
      $match = $this->getDeleted('https://api.cc.email' . $deleted->_links->next->href, $email);
    }

    return $match;
  }

  /**
   * Get Enabled Contact Lists 
   *
   * @param boolean $cached
   * @return array $lists of enabled lists
   * 
   * @see /Drupal/Form/ConstantContactLists.php
   */
  public function getEnabledContactLists($cached = true) {
    $lists = $this->getContactLists($cached);
    $enabled = $this->config->get('ik_constant_contact.enabled_lists')->getRawData();

    foreach ($lists as $id => $list) {
      if (!isset($enabled[$id]) || $enabled[$id] !== 1) {
        unset($lists[$id]);
      }
    }

    return $lists;
  }

  /**
   * Get the permanent link of a campaign.
   *
   * @param string $id
   *   The campaign id.
   *
   * @return null|string
   *   The URL of the campaign's permanent link.
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getPermaLinkFromCampaign(string $id) {
    $url = NULL;
    if (!$id) {
      return NULL;
    }
    $campaign = $this->getCampain($id);
    foreach ($campaign->campaign_activities as $activity) {
      if ($activity->role != 'permalink') {
        continue;
      }
      $act = $this->getCampaignActivity($activity->campaign_activity_id);
      if ($act) {
        return $act->permalink_url;
      }
    }
    return NULL;
  }

  /**
   * Handles an error
   *
   * @param [object] $error
   * @return [array] $return
   */
  protected function handleRequestException(object $e) {
    $response = $e->getResponse();
    $error = is_null($response) ? FALSE : json_decode($response->getBody());

    $message = 'RequestException: ';

    $errorInfo = [];

    if ($error && is_object($error)) {
      if (property_exists($error, 'error')) {
        $errorInfo[] = $error->error;
      }

      if (property_exists($error, 'message')) {
        $errorInfo[] = $error->message;
      }
  
      if (property_exists($error, 'error_description')) {
        $errorInfo[] = $error->error_description;
      }
  
      if (property_exists($error, 'errorCode')) {
        $errorInfo[] = $error->errorSummary;
      }
  
      if (property_exists($error, 'errorCode')) {
        $errorInfo[] = $error->errorCode;
      }
    }

    $message .= implode(', ', $errorInfo);

    $this->loggerFactory->error($message);

    // Return the error to show an error on form submission
    return ['error' => $message];
  }

  /**
   * Handles API response for adding a contact.
   *
   * @param object $response
   *   The json_decoded json response.
   * @param string $method
   *   The name of the method that the response came from.
   *
   * @return array
   *   Returns an array that includes the method name and
   *   the statuscode except if it is coming from getContact method.
   *   Then it returns an array of the contact that matches.
   */
  private function handleResponse($response, $method) {
    if (($response->getStatusCode() === 200) || ($response->getStatusCode() === 201 && $method === 'createContact')) {
      $json = json_decode($response->getBody()->getContents());

      $this->loggerFactory->info('@method has been executed successfully.', ['@method' => $method]);

      $this->updateTokenExpiration();

      if ($method === 'getContact') {
        return $json;
      }

      return [
        'method' => $method,
        'response' => $response->getStatusCode(),
      ];
    }
    else {
      $statuscode = $response->getStatusCode();
      $responsecode = $response->getReasonPhrase();

      $this->loggerFactory->error('Call to @method resulted in @status response. @responsecode', [
        '@method' => $method,
        '@status' => $statuscode,
        '@responsecode' => $responsecode,
      ]);

      return [
        'error' => 'There was a problem signing up. Please try again later.',
      ];
    }
  }

  /**
   * Submits a contact to the API. 
   * Used to be used on CostantContactBlockForm but using $this->submitContactForm instead.
   * Determine if contact needs to be updated or created.
   *
   * @param array $data
   *   Data to create/update a contact.
   *   Requires a 'email_address' key.
   *   But can also accept 'first_name' and 'last_name' key.
   * @param array $listIDs
   *   An array of list UUIDs to post the contact to.
   *
   * @return array
   *   Returns an error if there is an error.
   *   Otherwise it sends the info to other methods.
   *
   * @see $this->updateContact
   * @see $this->putContact
   * @see $this->createContact
   */
  public function postContact(array $data = [], $listIDs = []) {
    $config = $this->getConfig();
    $enabled = $this->config->get('ik_constant_contact.enabled_lists')->getRawData();

    if (!$config['client_id'] || !$config['client_secret'] || !$config['access_token'] || !$data) {
      $msg = 'Missing credentials for postContact';

      $this->loggerFactory->error($msg);

      return [
        'error' => $msg,
      ];
    }

    if (!$listIDs || count($listIDs) === 0) {
      $msg = 'A listID is required.';

      $this->loggerFactory->error($msg);

      return [
        'error' => $msg,
      ];
    }

    foreach ($listIDs as $listID) {
      if (!isset($enabled[$listID]) || $enabled[$listID] !== 1) {
        $msg = 'The listID provided does not exist or is not enabled.';
  
        $this->loggerFactory->error($msg);
  
        return [
          'error' => $msg,
        ];
      }
    }

    if (!isset($data['email_address'])) {
      $msg = 'An email address is required';

      $this->loggerFactory->error($msg);

      return [
        'error' => $msg,
      ];
    }

    // Refresh our tokens before every request.
    $this->refreshToken();

    // Check if contact already exists.
    $exists = (array) $this->getContact($data);

    // If yes, updateContact.
    // If no, createContact.
    // If previous deleted, putContact.
    if (isset($exists['contacts']) && count($exists['contacts']) > 0) {
      $this->updateContact($data, $exists['contacts'][0], $listIDs);
    }
    elseif ($exists && isset($exists['deleted_at'])) {
      $this->putContact($exists, $data, $listIDs);
    }
    else {
      $this->createContact($data, $listIDs);
    }
  }

  /**
   * Updates a contact if it already exists and has been deleted.
   *
   * @param array $contact
   *   The response from $this->getDeleted.
   * @param array $data
   *   The $data provided originally. @see $this->postContact.
   * @param array $listIDs
   *   The list IDs we want to add contact to.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/putContact
   * @see $this->getDeleted
   *
   * @TODO perhaps combine this with updateContact. The difference is that $contact is
   * an array here and an object in updateContact.
   */
  private function putContact(array $contact, array $data, $listIDs) {
    $config = $this->getConfig();

    $body = (object) $contact;

    $body = $this->buildResponseBody($data, $body);

    $body->email_address->permission_to_send = 'implicit';
    // To resubscribe a contact after an unsubscribe update_source must equal Contact. 
    // @see https://v3.developer.constantcontact.com/api_guide/contacts_re-subscribe.html#re-subscribing-contacts
    $body->update_source = 'Contact';
    $body->list_memberships = $listIDs;

    $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
    $this->moduleHandler->invokeAll('ik_constant_contact_contact_update_data_alter', [$data, &$body]);

    try {
      $response = $this->httpClient->request('PUT', $config['contact_url'] . '/' . $contact['contact_id'], [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
        'body' => json_encode($body),
      ]);

      $this->handleResponse($response, 'putContact');

    }
    catch (RequestException $e) {
      // Return the error to show an error on form submission
      return $this->handleRequestException($e);
    }
    catch (ClientException $e) {
      return $this->handleRequestException($e);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }

  /**
   * Makes authenticated request to Constant Contact to refresh tokens.
   *
   * @see https://v3.developer.constantcontact.com/api_guide/server_flow.html#refreshing-an-access-token
   */
  public function refreshToken($updateLists = true) {
    $config = $this->getConfig();

    if (!$config['client_id'] || (!$config['client_secret'] && $config['auth_type'] === 'auth_flow') || !$config['refresh_token']) {
      return FALSE;
    }

    // @TODO - Fix for pkce flow. 
    // @see https://www.drupal.org/project/ik_constant_contact/issues/3285446
    // @see https://v3.developer.constantcontact.com/api_guide/pkce_flow.html
    try {
      $response = $this->httpClient->request('POST', $this->tokenUrl, [
        'headers' => [
          'Authorization' => 'Basic ' . base64_encode($config['client_id'] . ':' . $config['client_secret']),
        ],
        'form_params' => [
          'refresh_token' => $config['refresh_token'],
          'grant_type' => 'refresh_token',
        ],
      ]);

      $json = json_decode($response->getBody()->getContents());

      $this->saveTokens($json);

      if ($updateLists === true) {
        $this->getContactLists(false);
      }
    }
    catch (RequestException $e) {
      return $this->handleRequestException($e);
    }
    catch (\Exception $e) {
      dpm($e);
      $this->loggerFactory->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }

  /**
   * Saves available contact lists to a cache.
   *
   * @param array $data
   *   An array of lists and list UUIDs from $this->getContactLists.
   */
  public function saveContactLists(array $data) {
    $cid = 'ik_constant_contact.lists';
    $enabled = $this->config->get('ik_constant_contact.enabled_lists')->getRawData();
    
    // Add an enabled flag to the list data.
    foreach ($data as $key => $value) {
      $data[$key]->enabled = (isset($enabled[$key]) && $enabled[$key] === 1);
      $data[$key]->cached_on = strtotime('now'); 
    }

    $this->cache->set($cid, $data);
  }

  /**
   * Saves access and refresh tokens to our config database.
   *
   * @param object $data
   *   Data object of data to save the token.
   *
   * @see $this->refreshToken
   */
  private function saveTokens($data) {
    if ($data && property_exists($data, 'access_token') && property_exists($data, 'refresh_token')) {
      $tokens = $this->config->getEditable('ik_constant_contact.tokens');
      $tokens->clear('ik_constant_contact.tokens');
      $tokens->set('access_token', $data->access_token);
      $tokens->set('refresh_token', $data->refresh_token);
      $tokens->set('timestamp', strtotime('now'));

      if ($data->expires_in < $tokens->get('expires')) {
        $tokens->set('expires', $data->expires_in);
      }

      $tokens->save();

      $this->loggerFactory->info('New tokens saved at ' . date('Y-m-d h:ia', strtotime('now')));
    }
    else {
      $this->loggerFactory->error('There was an error saving tokens');
    }
  }

  /**
   * Submission of contact form.
   * Replaces $this->postContact as of v. 2.0.9.
   *
   * @param array $data
   *   Data to create/update a contact.
   *   Requires a 'email_address' key.
   *   But can also accept 'first_name' and 'last_name' key.
   * @param array $listIDs
   *   An array of list UUIDs to post the contact to.
   *
   * @return array
   *   Returns an error if there is an error.
   *   Otherwise it sends the info to other methods.
   *
   * @see https://v3.developer.constantcontact.com/api_guide/contacts_create_or_update.html
   */
  public function submitContactForm(array $data = [], $listIDs = []) {
    $config = $this->getConfig();
    $enabled = $this->config->get('ik_constant_contact.enabled_lists')->getRawData();

    if (!$config['client_id'] || !$config['client_secret'] || !$config['access_token'] || !$data) {
      $msg = 'Missing credentials for postContact';

      $this->loggerFactory->error($msg);

      return [
        'error' => $msg,
      ];
    }

    if (!$listIDs || count($listIDs) === 0) {
      $msg = 'A listID is required.';

      $this->loggerFactory->error($msg);

      return [
        'error' => $msg,
      ];
    }

    foreach ($listIDs as $listID) {
      if (!isset($enabled[$listID]) || $enabled[$listID] !== 1) {
        $msg = 'The listID provided does not exist or is not enabled.';
  
        $this->loggerFactory->error($msg);
  
        return [
          'error' => $msg,
        ];
      }
    }

    if (!isset($data['email_address'])) {
      $msg = 'An email address is required';

      $this->loggerFactory->error($msg);

      return [
        'error' => $msg,
      ];
    }

    // Refresh our tokens before every request.
    $this->refreshToken();

    $body = (object)[];

    $body = $this->buildResponseBody($data, $body);

    // Add required fields.
    $body->email_address = $data['email_address'];
    $body->list_memberships = $listIDs;

    $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
    $this->moduleHandler->invokeAll('ik_constant_contact_contact_form_submission_alter', [$data, &$body]);

    try {
      $response = $this->httpClient->request('POST', $config['contact_url'] . '/sign_up_form', [
        'headers' => [
          'Authorization' => 'Bearer ' . $config['access_token'],
          'cache-control' => 'no-cache',
          'content-type' => 'application/json',
          'accept' => 'application/json',
        ],
        'body' => json_encode($body),
      ]);

      $this->handleResponse($response, 'submitContactForm');

    }
    catch (RequestException $e) {
      // Return the error to show an error on form submission
      return $this->handleRequestException($e);
    }
    catch (ClientException $e) {
      $this->handleRequestException($e);
    }
    catch (\Exception $e) {
      $this->loggerFactory->error($e);

      // Return the error to show an error on form submission
      return ['error' => $e];
    }
  }


  /**
   *  Unsubscribes a contact from all lists.
   *
   * @param array $contact
   *   The response from $this->getDeleted.
   * @param array $data
   *   The $data provided
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/putContact
   *
   */
  public function unsubscribeContact(array $data) {
    $config = $this->getConfig();
    // Check if contact already exists.
    $exists = (array) $this->getContact($data);
    $body = null;

    if (isset($exists['contacts']) && count($exists['contacts']) > 0) {
      $body = (object) $exists['contacts'][0];
      $exists = $exists['contacts'][0];
    }
    elseif ($exists && isset($exists['deleted_at'])) {
      $body = (object) $exists;
    }

    if ($body) {
      $body = $this->buildResponseBody($data, $body);

      $body->email_address->permission_to_send = 'unsubscribed';
      // To resubscribe a contact after an unsubscribe update_source must equal Contact. 
      // @see https://v3.developer.constantcontact.com/api_guide/contacts_re-subscribe.html#re-subscribing-contacts
      $body->update_source = 'Contact';

      $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
      $this->moduleHandler->invokeAll('ik_constant_contact_contact_unsubscribe_data_alter', [$data, &$body]);

      try {
        $response = $this->httpClient->request('PUT', $config['contact_url'] . '/' . $exists->contact_id, [
          'headers' => [
            'Authorization' => 'Bearer ' . $config['access_token'],
            'cache-control' => 'no-cache',
            'content-type' => 'application/json',
            'accept' => 'application/json',
          ],
          'body' => json_encode($body),
        ]);

        $this->handleResponse($response, 'unsubscribeContact');

      }
      catch (RequestException $e) {
        // Return the error to show an error on form submission
        return $this->handleRequestException($e);
      }
      catch (ClientException $e) {
        return $this->handleRequestException($e);
      }
      catch (\Exception $e) {
        $this->loggerFactory->error($e);

        // Return the error to show an error on form submission
        return ['error' => $e];
      }
    }

  }

  /**
   * Updates a contact if it already exists on a list.
   *
   * @param array $data
   *   Provided data to update the contact with.
   * @param object $contact
   *   Contact match to provided data.
   * @param array $listIDs
   *   An array of list UUIDs that the contact is being updated on.
   *
   * @see https://v3.developer.constantcontact.com/api_reference/index.html#!/Contacts/putContact
   */
  private function updateContact(array $data, $contact, $listIDs) {
    $config = $this->getConfig();

    if ($contact && property_exists($contact, 'contact_id')) {
      
      $body = $contact;
      $body = $this->buildResponseBody($data, $body);

      // Add required fields
      $body->email_address->address = $data['email_address'];
      $body->email_address->permission_to_send = 'implicit';
      $body->update_source = 'Contact';
      $body->list_memberships = $listIDs;

      $this->moduleHandler->invokeAll('ik_constant_contact_contact_data_alter', [$data, &$body]);
      $this->moduleHandler->invokeAll('ik_constant_contact_contact_update_data_alter', [$data, &$body]);

      try {
        $response = $this->httpClient->request('PUT', $config['contact_url'] . '/' . $contact->contact_id, [
          'headers' => [
            'Authorization' => 'Bearer ' . $config['access_token'],
            'cache-control' => 'no-cache',
            'content-type' => 'application/json',
            'accept' => 'application/json',
          ],
          'body' => json_encode($body),
        ]);

        return $this->handleResponse($response, 'updateContact');

      }
      catch (RequestException $e) {
        // Return the error to show an error on form submission
        return $this->handleRequestException($e);
      }
      catch (ClientException $e) {
        $this->handleRequestException($e);
      }
      catch (\Exception $e) {
        $this->loggerFactory->error($e);
  
        // Return the error to show an error on form submission
        return ['error' => $e];
      }
    }
    else {
      $this->loggerFactory->error('error: No contact id provided for updateContact method');
      return ['error: No contact id provided'];
    }
  }

  protected function updateTokenExpiration() {
    $tokens = $this->config->getEditable('ik_constant_contact.tokens');
    $tokens->set('expires', strtotime('now +2 hours'));
    $tokens->save();
  }

}
