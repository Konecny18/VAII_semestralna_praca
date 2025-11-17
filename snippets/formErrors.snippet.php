// code snippet

private function formErrors(): array
{
    $errors = [];
    if ($this->request()->file('picture')->getName() == "") {
        $errors[] = "Pole Súbor obrázka musí byť vyplnené!";
    }
    if ($this->request()->value('text') == "") {
        $errors[] = "Pole Text príspevku musí byť vyplnené!";
    }
    if ($this->request()->file('picture')->getName() != "" &&
        !in_array($this->request()->file('picture')->getType(), ['image/jpeg', 'image/png'])) {
        $errors[] = "Obrázok musí byť typu JPG alebo PNG!";
    }
    if ($this->request()->value('text') != "" && strlen($this->request()->value('text') < 5)) {
        $errors[] = "Počet znakov v text príspevku musí byť viac ako 5!";
    }
    return $errors;
}