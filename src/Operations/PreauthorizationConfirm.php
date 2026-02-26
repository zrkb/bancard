<?php

declare(strict_types=1);

namespace Bancard\Operations;

use Bancard\Response\PreauthorizationConfirmResponse;
use Bancard\Util\Token;

/**
 * @extends Operation<PreauthorizationConfirmResponse>
 */
class PreauthorizationConfirm extends Operation
{
    protected string $endpoint = '/vpos/api/0.3/preauthorizations/confirm';

    /** @var class-string<PreauthorizationConfirmResponse> */
    protected string $responseClass = PreauthorizationConfirmResponse::class;

    public function token(): string
    {
        return Token::make(
            $this->client->privateKey,
            (string) $this->payload('shop_process_id'),
            'pre-authorization-confirm',
        );
    }

    /**
     * @return list<string>
     */
    protected function rules(): array
    {
        return ['shop_process_id'];
    }
}
