<?php

namespace App\Service;

class ValidateService
{
    /**
     * Форматирует ошибки валидации в массив строк.
     *
     * @param mixed $validErrors
     * @return array
     */
    public function formatValidationErrors($validErrors): array
    {
        $errors = [];
        foreach ($validErrors as $validError) {
            $errors[] = [
                'field' => $validError->getPropertyPath(),
                'message' => $validError->getMessage()
            ];
        }
        return $errors;
    }
}