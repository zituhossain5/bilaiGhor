<?php

/*
 * Printed receipt (manual orders) and short product names.
 *
 * The admin-editable settings (dense style threshold, comma-list mode) live in General
 * Settings; this file holds the word lists the short-name suggester uses.
 */
return [

    // Longest a product's short name may be (matches the products.short_name column).
    'short_name_max' => 40,

    /*
     * Phrases removed when suggesting a short name. Matched case-insensitively as whole words,
     * longest first, so "Wet Cat Food" goes before "Cat Food" gets a chance.
     */
    'filler_words' => [
        'Wet Cat Food', 'Dry Cat Food', 'Wet Dog Food', 'Dry Dog Food',
        'Cat Wet Food', 'Cat Dry Food', 'Dog Wet Food', 'Dog Dry Food',
        'Cat Canned Food', 'Canned Cat Food', 'Canned Food',
        'Cat Food', 'Dog Food', 'Kitten Food', 'Wet Food', 'Dry Food',
        'for Cats & Dogs', 'for Cats and Dogs', 'for Dogs & Cats', 'for Dogs and Cats',
        'for Adult Cats', 'for Adult Cat', 'for Adult', 'for Cats', 'for Cat', 'for Kittens', 'for Kitten',
        'for Dogs', 'for Dog',
        'Adult', 'Flavor', 'Flavour', 'Flavored', 'Flavoured',
    ],

    /*
     * Packaging words ("Pouch") removed only when they are redundant: at least this many
     * words still describe the product without them. "Whiskas Pouch Mackerel" keeps "Pouch".
     */
    'format_words' => ['Pouch', 'Canned', 'Can'],
    'format_words_keep_below' => 3,

];
