<?php

/** @var Framework\Support\LinkGenerator $link */
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-10 d-flex gap-4  flex-column">
            <form method="post" action="">
                <label for="inputGroupFile02" class="form-label">Súbor obrázka</label>
                <div class="input-group mb-3 has-validation">
                    <input type="text" class="form-control" name="picture" id="inputGroupFile02">
                    <!--<input type="file" class="form-control " name="picture" id="inputGroupFile02">-->
                </div>
                <label for="post-text" class="form-label">Text príspevku</label>
                <div class="input-group has-validation mb-3 ">
                    <textarea class="form-control" aria-label="With textarea" name="text" id="post-text"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Uložiť</button>
            </form>
        </div>
    </div>
</div>