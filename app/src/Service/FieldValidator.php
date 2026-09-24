<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AttributeOption;
use App\Entity\AttributeType;
use App\Entity\CvAttribute;
use App\Entity\Project;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class FieldValidator
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function valueErrors(CvAttribute $attribute, array $input, ?AttributeOption $option): array
    {
        $violations = match ($attribute->getType()) {
            AttributeType::String => $this->validator->validate($this->text($input['string'] ?? null), [
                new Assert\Length(max: 255),
            ]),
            AttributeType::Text => $this->validator->validate($this->text($input['text'] ?? null), [
                new Assert\Length(max: 20000),
            ]),
            AttributeType::Numeric => $this->numeric($input['numeric'] ?? null),
            AttributeType::Date => $this->date($input['date'] ?? null),
            AttributeType::Period => $this->period($input),
            AttributeType::Boolean => $this->boolean($input['boolean'] ?? null),
            AttributeType::Image => $this->url($input['imageUrl'] ?? null),
            AttributeType::OneOfMany => $this->choice($input, $option),
        };

        return $this->messages($violations);
    }

    public function projectErrors(Project $project): array
    {
        return $this->messages($this->validator->validate($project));
    }

    public function tagErrors(array $names): array
    {
        $errors = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $errors = array_merge($errors, $this->messages($this->validator->validate($name, [
                new Assert\Length(max: 64),
            ])));
        }

        return $errors;
    }

    private function numeric(mixed $value): ConstraintViolationListInterface
    {
        $value = $this->text($value);
        if ($value === null) {
            return $this->validator->validate(null);
        }

        return $this->validator->validate($value, [
            new Assert\Regex(pattern: '/^-?\d{1,8}(\.\d{1,4})?$/', message: 'field.not_numeric'),
        ]);
    }

    private function date(mixed $value): ConstraintViolationListInterface
    {
        $value = $this->text($value);
        if ($value === null) {
            return $this->validator->validate(null);
        }

        return $this->validator->validate($value, [
            new Assert\Date(message: 'field.invalid_date'),
        ]);
    }

    private function period(array $input): ConstraintViolationListInterface
    {
        $start = $this->text($input['periodStart'] ?? null);
        $end = $this->text($input['periodEnd'] ?? null);
        $violations = $this->validator->validate(null);
        if ($start !== null) {
            $violations->addAll($this->validator->validate($start, [new Assert\Date(message: 'field.invalid_date')]));
        }
        if ($end !== null) {
            $violations->addAll($this->validator->validate($end, [new Assert\Date(message: 'field.invalid_date')]));
        }
        if (count($violations) === 0 && $start !== null && $end !== null && $end < $start) {
            $violations->addAll($this->validator->validate($end, [
                new Assert\GreaterThanOrEqual(value: $start, message: 'field.period_order'),
            ]));
        }

        return $violations;
    }

    private function boolean(mixed $value): ConstraintViolationListInterface
    {
        if ($value === null || $value === '') {
            return $this->validator->validate(null);
        }

        return $this->validator->validate((string) $value, [
            new Assert\Choice(choices: ['0', '1', 'true', 'false', 'on'], message: 'field.invalid_choice'),
        ]);
    }

    private function url(mixed $value): ConstraintViolationListInterface
    {
        $value = $this->text($value);
        if ($value === null) {
            return $this->validator->validate(null);
        }

        return $this->validator->validate($value, [
            new Assert\Url(protocols: ['https'], requireTld: true, message: 'field.invalid_url'),
        ]);
    }

    private function choice(array $input, ?AttributeOption $option): ConstraintViolationListInterface
    {
        $optionId = (int) ($input['optionId'] ?? 0);
        if ($optionId <= 0) {
            return $this->validator->validate(null);
        }
        if ($option instanceof AttributeOption) {
            return $this->validator->validate(null);
        }

        return $this->validator->validate('', [
            new Assert\NotBlank(message: 'field.invalid_choice'),
        ]);
    }

    private function messages(ConstraintViolationListInterface $violations): array
    {
        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = $violation->getMessage();
        }

        return $messages;
    }

    private function text(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
