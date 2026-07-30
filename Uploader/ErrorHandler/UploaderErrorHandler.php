<?php

declare(strict_types=1);

namespace Symbio\OrangeGate\MediaBundle\Uploader\ErrorHandler;

use Exception;
use Oneup\UploaderBundle\Uploader\ErrorHandler\ErrorHandlerInterface;
use Oneup\UploaderBundle\Uploader\Response\AbstractResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class UploaderErrorHandler implements ErrorHandlerInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function addException(AbstractResponse $response, Exception $exception): void
    {
        $message = $this->translator->trans($exception->getMessage(), [], 'SymbioOrangeGateMediaBundle');
        $response['error'] = $message;
    }
}
