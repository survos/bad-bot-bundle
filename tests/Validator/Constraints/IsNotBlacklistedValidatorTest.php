<?php declare(strict_types = 1);

namespace LSBProject\BlacklistBundle\Tests\Validator\Constraints;

use LSBProject\BlacklistBundle\Entity\BlacklistManagerInterface;
use LSBProject\BlacklistBundle\Type\TypeInterface;
use LSBProject\BlacklistBundle\Utils\TypeExtractor;
use LSBProject\BlacklistBundle\Validator\Constraints\IsNotBlacklisted;
use LSBProject\BlacklistBundle\Validator\Constraints\IsNotBlacklistedValidator;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface as ExecutionContextInterfaceAlias;

class IsNotBlacklistedValidatorTest extends TestCase
{
    /** @var BlacklistManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $bm;

    /** @var IsNotBlacklisted|\PHPUnit\Framework\MockObject\MockObject */
    private $constraint;

    public function setUp(): void
    {
        parent::setUp();

        $this->bm = $this->createMock(BlacklistManagerInterface::class);

        $this->constraint = $this->createMock(IsNotBlacklisted::class);
        $this->constraint->type = 'foo';
    }

    public function testValidate(): void
    {
        $extractor = $this->createMock(TypeExtractor::class);
        $extractor
            ->expects($this->once())
            ->method('extractSupported')
            ->willReturn([
                $this->mockType(true, true, $this->once()),
                $this->mockType(false, true, $this->never()),
                $this->mockType(true, true, $this->once()),
            ]);

        $validator = new IsNotBlacklistedValidator($this->bm, $extractor);
        $validator->initialize(
            $this->createMock(ExecutionContextInterfaceAlias::class)
        );
        $validator->validate('123', $this->constraint);
    }

    public function testValidateDoesntSatisfy(): void
    {
        $extractor = $this->createMock(TypeExtractor::class);
        $extractor
            ->expects($this->once())
            ->method('extractSupported')
            ->willReturn([
                $this->mockType(true, true, $this->once()),
                $this->mockType(true, false, $this->never()),
                $this->mockType(true, true, $this->never()),
            ]);

        $validator = new IsNotBlacklistedValidator($this->bm, $extractor);
        $validator->initialize(
            $this->createMock(ExecutionContextInterfaceAlias::class)
        );

        $this->expectException(\InvalidArgumentException::class);

        $validator->validate('123', $this->constraint);
    }

    private function mockType(bool $supports, bool $satisfies, InvokedCount $count): TypeInterface
    {
        $type = $this->createMock(TypeInterface::class);
        $type->method('supports')->willReturn($supports);
        $type->method('satisfies')->willReturn($satisfies);
        $type->expects($count)->method('validate');

        return $type;
    }
}
