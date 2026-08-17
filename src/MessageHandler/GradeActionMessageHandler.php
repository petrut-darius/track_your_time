<?php

namespace App\MessageHandler;

use App\Action\GradeAction;
use App\Message\GradeActionMessage;
use App\Service\GradingService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GradeActionMessageHandler
{
    public function __construct(private GradingService $gradingService, private GradeAction $gradeAction)
    {

    }

    public function __invoke(GradeActionMessage $message): void
    {
        $grades = $this->gradingService->handle();
        $this->gradeAction->handle($grades);
    }
}
