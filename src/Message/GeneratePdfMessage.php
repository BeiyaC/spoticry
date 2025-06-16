<?php

namespace App\Message;

final class GeneratePdfMessage
{
    public function __construct(
        private int $articleId,
        private string $userEmail
    ) {
    }

    public function getArticleId(): int
    {
        return $this->articleId;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }
}
