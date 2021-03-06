<?php namespace Mollie\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Mollie extends AbstractProvider
{
	use BearerAuthorizationTrait;

	/**
	 * The base url to the Mollie API.
	 *
	 * @const string
	 */
	const MOLLIE_API_URL = 'https://api.mollie.nl';

	/**
	 * The base url to the Mollie web application.
	 *
	 * @const string
	 */
	const MOLLIE_WEB_URL = 'https://www.mollie.com';

	/**
	 * Shortcuts to the available Mollie scopes.
	 *
	 * In order to access the Mollie API endpoints on behalf of your app user, your
	 * app should request the appropriate scope permissions.
	 *
	 * @see https://www.mollie.com/en/docs/oauth/permissions
	 */
	const SCOPE_PAYMENTS_READ       = 'payments.read';
	const SCOPE_PAYMENTS_WRITE      = 'payments.write';
	const SCOPE_REFUNDS_READ        = 'refunds.read';
	const SCOPE_REFUNDS_WRITE       = 'refunds.write';
	const SCOPE_CUSTOMERS_READ      = 'customers.read';
	const SCOPE_CUSTOMERS_WRITE     = 'customers.write';
	const SCOPE_PROFILES_READ       = 'profiles.read';
	const SCOPE_PROFILES_WRITE      = 'profiles.write';
	const SCOPE_SETTLEMENTS_READ    = 'settlements.read';
	const SCOPE_ORGANIZATIONS_READ  = 'organizations.read';
	const SCOPE_ORGANIZATIONS_WRITE = 'organizations.write';

	/**
	 * Returns the base URL for authorizing a client.
	 *
	 * Eg. https://oauth.service.com/authorize
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl ()
	{
		return static::MOLLIE_WEB_URL . '/oauth2/authorize';
	}

	/**
	 * Returns the base URL for requesting an access token.
	 *
	 * Eg. https://oauth.service.com/token
	 *
	 * @param array $params
	 * @return string
	 */
	public function getBaseAccessTokenUrl (array $params)
	{
		return static::MOLLIE_API_URL . '/oauth2/tokens';
	}

	/**
	 * Returns the URL for requesting the app user's details.
	 *
	 * @param AccessToken $token
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl (AccessToken $token)
	{
		return static::MOLLIE_API_URL . '/v1/organizations/me';
	}

	/**
	 * The Mollie OAuth provider requests access to the organizations.read scope
	 * by default to enable retrieving the app user's details.
	 *
	 * @return string[]
	 */
	protected function getDefaultScopes ()
	{
		return [
			self::SCOPE_ORGANIZATIONS_READ,
		];
	}

	/**
	 * Returns the string that should be used to separate scopes when building
	 * the URL for requesting an access token.
	 *
	 * @return string Scope separator, defaults to ','
	 */
	protected function getScopeSeparator ()
	{
		return ' ';
	}

	/**
	 * Checks a provider response for errors.
	 *
	 * @throws IdentityProviderException
	 * @param  ResponseInterface $response
	 * @param  array|string      $data Parsed response data
	 * @return void
	 */
	protected function checkResponse (ResponseInterface $response, $data)
	{
		if ($response->getStatusCode() >= 400)
		{
			if (isset($data['error']))
			{
				if (isset($data['error']['type']) && isset($data['error']['message'])) {
					$message = sprintf('[%s] %s', $data['error']['type'], $data['error']['message']);
				} else {
					$message = $data['error'];
				}

				if (isset($data['error']['field']))
				{
					$message .= sprintf(' (field: %s)', $data['error']['field']);
				}
			}
			else
			{
				$message = $response->getReasonPhrase();
			}

			throw new IdentityProviderException($message, $response->getStatusCode(), $response);
		}
	}

	/**
	 * Generates a resource owner object from a successful resource owner
	 * details request.
	 *
	 * @param  array       $response
	 * @param  AccessToken $token
	 * @return ResourceOwnerInterface
	 */
	protected function createResourceOwner (array $response, AccessToken $token)
	{
		return new MollieResourceOwner($response);
	}
}