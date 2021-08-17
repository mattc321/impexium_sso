<?php
namespace Drupal\impexium_sso\Api\Response;

use Drupal\impexium_sso\Api\Model\Response\ImpexiumUser;
use Drupal\impexium_sso\Api\Model\Response\ResponseModelInterface;
use Drupal\impexium_sso\Exception\Types\ApiConnectionException;
use Drupal\impexium_sso\Exception\Types\EmptyResponseException;
use Psr\Http\Message\ResponseInterface;

class ResponseHandler
{
  /**
   * @param ResponseInterface $response
   * @param string $classToReturn
   * @return ResponseModelInterface
   * @throws ApiConnectionException
   * @throws EmptyResponseException
   */
  public function handleResponse(ResponseInterface $response, string $classToReturn)
  {

    if ($response->getStatusCode()  !== 200) {
      throw new ApiConnectionException(
        "Could not connect to impexium. 
        Status Code: {$response->getStatusCode()}"
      );
    }

    if (! $data = json_decode($response->getBody(), true)) {
      throw new EmptyResponseException('Response body is empty.');
    }

    switch ($classToReturn) {
      case ImpexiumUser::class:
        //for user records we are nested
        if (! isset($data['dataList']) || ! $data['dataList']) {
          throw new EmptyResponseException('No user data returned from API');
        }
        $data = $data['dataList'][0]; //dont need to handle paging currently
        break;
    }

    return new $classToReturn($data);
  }
}
