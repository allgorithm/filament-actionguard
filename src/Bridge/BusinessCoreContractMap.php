<?php

declare(strict_types=1);

namespace Allgorithm\FilamentActionGuard\Bridge;

final readonly class BusinessCoreContractMap
{
    public function __construct(
        public string $operationContract = 'BusinessCore\\Contracts\\Operations\\ApplicationOperationContract',
        public string $descriptor = 'BusinessCore\\Application\\Operations\\Descriptor\\OperationDescriptor',
        public string $guardContract = 'BusinessCore\\Contracts\\Guards\\OperationGuardContract',
        public string $actorContext = 'BusinessCore\\Application\\Context\\ActorContext',
        public string $operationContext = 'BusinessCore\\Application\\Context\\OperationContext',
        public string $checkResult = 'BusinessCore\\Guards\\CheckResult',
    ) {}

    public function isAvailable(): bool
    {
        return interface_exists($this->operationContract)
            && class_exists($this->descriptor)
            && interface_exists($this->guardContract)
            && class_exists($this->actorContext)
            && class_exists($this->operationContext)
            && class_exists($this->checkResult);
    }
}
