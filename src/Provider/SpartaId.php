<?php declare (strict_types=1);

namespace Apploud\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;

final class SpartaId extends AbstractProvider
{
    use BearerAuthorizationTrait;

    public const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'id';

    /**
     * @var string
     */
    protected $environment = SpartaIdEnvironment::DEVELOPMENT;

    /**
     * @var string
     */
    private $returnUrl;


    public function setReturnUrl(string $url): void
    {
        $this->returnUrl = $url;
    }


    public function getBaseAuthorizationUrl(): string
    {
        return $this->getBaseUrl() . '/oauth2/authorize?returnUrl=' . $this->getReturnUrl();
    }


    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->getBaseUrl() . '/oauth2/access-token';
    }


    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->getBaseUrl() . '/api/1.0/profile?returnUrl=' . $this->getReturnUrl();
    }


    public function getProfileUrl(): string
    {
        return $this->getBaseUrl() . '/?returnUrl=' . $this->getReturnUrl();
    }


    public function getEditProfileUrl(): string
    {
        return $this->getBaseUrl() . '/user/edit-profile?returnUrl=' . $this->getReturnUrl();
    }


    public function getRegistrationUrl(): string
    {
        return $this->getBaseUrl() . '/sign/up?returnUrl=' . $this->getReturnUrl();
    }


    public function getOrderKidMembershipUrl(): string
	{
		return $this->getBaseUrl() . '/api/1.0/orders/memberships/associated';
	}


	public function getOrderMembershipUrl(): string
	{
		return $this->getBaseUrl() . '/membership/buy?returnUrl=' . $this->getReturnUrl();
	}


	public function getKidMembershipOrderRecapitulationUrl(string $orderId): string
	{
		return $this->getBaseUrl() . "/associated-membership/order-recapitulation?orderId=$orderId&returnUrl=" . $this->getReturnUrl();
	}


	public function getLogoutUrl(): string
	{
		return $this->getBaseUrl() . '?do=logout&returnUrl=' . $this->getReturnUrl();
	}


	public function getHasSpartaCampMembershipUrl(): string
	{
		return $this->getBaseUrl() . '/api/1.0/orders/memberships/spartacamp';
	}


    public function getBaseUrl(): string
    {
        return SpartaIdEnvironment::BASE_URL[$this->environment];
    }


	public function logWebPageLoad(AccessToken $accessToken, string $url, ?string $ipAddress, ?string $userAgent): void
	{
		$data = [
			'url' => $url,
			'ipAddress' => $ipAddress ?: null,
			'userAgent' => $userAgent ?: null,
		];

		$request = $this->getAuthenticatedRequest(
			'POST',
			$this->getBaseUrl() . '/api/1.0/user/activities/web-page-load',
			$accessToken,
			[
				'body' => Json::encode($data),
			]
		);

		$this->getHttpClient()->send($request);
	}


    /**
     * @return string[]
     */
    protected function getDefaultScopes(): array
    {
        return ['profile'];
    }


    protected function checkResponse(ResponseInterface $response, $data): void
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException(
                $data['message'] ?: $response->getReasonPhrase(),
                $response->getStatusCode(),
                (array) $response
            );
        }
    }


    protected function createResourceOwner(array $response, AccessToken $token): SpartaIdResourceOwner
    {
        return new SpartaIdResourceOwner($response);
    }


    protected function getReturnUrl(): string
    {
        if ($this->returnUrl) {
            return urlencode($this->returnUrl);
        }

        if (!isset($_SERVER['REQUEST_URI'])) {
            return '';
        }

        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $actualLink = $protocol . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        return urlencode($actualLink);
    }


}
