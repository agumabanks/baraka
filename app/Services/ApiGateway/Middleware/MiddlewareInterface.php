<?php

namespace App\Services\ApiGateway\Middleware;

use App\Services\ApiGateway\ApiGatewayContext;

/**
 * Interface for API Gateway middleware
 */
interface MiddlewareInterface
{
    /**
     * Process the request through the middleware
     *
     * @param ApiGatewayContext $context
     * @return bool|Response Returns true to continue processing, or Response to stop
     */
    public function handle(ApiGatewayContext $context): bool;

    /**
     * Set the next middleware in the chain
     *
     * @param MiddlewareInterface $next
     * @return self
     */
    public function setNext(MiddlewareInterface $next): self;

    /**
     * Get the next middleware in the chain
     *
     * @return MiddlewareInterface|null
     */
    public function getNext(): ?MiddlewareInterface;

    /**
     * Get middleware priority (lower numbers execute first)
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Check if middleware should be executed for this request
     *
     * @param ApiGatewayContext $context
     * @return bool
     */
    public function shouldExecute(ApiGatewayContext $context): bool;

    /**
     * Get middleware name
     *
     * @return string
     */
    public function getName(): string;
}