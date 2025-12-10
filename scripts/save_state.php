<?php

function save_state(string $file, array $data): void {
    file_put_contents($file, json_encode($data));
}
