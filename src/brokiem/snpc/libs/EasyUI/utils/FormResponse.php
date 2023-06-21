<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);


namespace brokiem\snpc\libs\EasyUI\utils;


use brokiem\snpc\libs\EasyUI\element\Dropdown;
use brokiem\snpc\libs\EasyUI\element\Element;
use brokiem\snpc\libs\EasyUI\element\Input;
use brokiem\snpc\libs\EasyUI\element\Selector;
use brokiem\snpc\libs\EasyUI\element\Slider;
use brokiem\snpc\libs\EasyUI\element\StepSlider;
use brokiem\snpc\libs\EasyUI\element\Toggle;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;

class FormResponse {

    /** @var Element[] */
    private array $elements;

    /**
     * FormResponse constructor.
     * @param Element[] $elements
     */
    public function __construct(array $elements) {
        $this->elements = $elements;
    }

    public function getInputSubmittedText(string $inputId): string|null {
        return (!is_null($this->getElement($inputId, Input::class))) ?
                $this->getElement($inputId, Input::class)->getSubmittedText() : null;
    }

    public function getToggleSubmittedChoice(string $toggleId): bool|null {
        return (!is_null($this->getElement($toggleId, Toggle::class))) ?
                $this->getElement($toggleId, Input::class)->getSubmittedChoice() : null;
    }

    public function getSliderSubmittedStep(string $sliderId): float|null {
        return (!is_null($this->getElement($sliderId, Slider::class))) ?
                $this->getElement($sliderId, Slider::class)->getSubmittedStep() : null;
    }

    public function getStepSliderSubmittedOptionId(string $sliderId): string|null {
        return (!is_null($this->getElement($sliderId, StepSlider::class))) ?
                $this->getElement($sliderId, StepSlider::class)->getSubmittedOptionId() : null;
    }

    public function getDropdownSubmittedOptionId(string $dropdownId): string|null {
        return (!is_null($this->getElement($dropdownId, Dropdown::class))) ?
                $this->getElement($dropdownId, Dropdown::class)->getSubmittedOptionId() : null;
    }

    /**
     * @param string $id
     * @param string $expectedClass
     * @return Element|Input|Toggle|Slider|Selector
     */
    private function getElement(string $id, string $expectedClass): Element|null {
        $element = $this->elements[$id] ?? null;

        if(!$element instanceof Element) {
            //throw new InvalidArgumentException("$id is not a valid element identifier");
            return null;
        } elseif(!is_a($element, $expectedClass)) {
            try {
                throw new InvalidArgumentException("The element with $id is not a " . (new ReflectionClass($expectedClass))->getShortName());
            } catch(ReflectionException $exception) {
                throw new InvalidArgumentException($expectedClass . " doesn't use a valid... namespace?");
            } finally {
                return null;
            }
        }
        return $element;
    }

}