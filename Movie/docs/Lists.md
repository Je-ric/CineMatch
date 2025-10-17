Movie App — Lists, Filters, and Recommendations

Index: Global (what everyone sees), Trending, Popular
Profile: Favorites, Rated, Recommendations

Filter/Search (index)
    - New (latest)
    - Trending / Popular (rating)
    - Genre
    - Release Year
    - Country
    - Language

More like this (viewMovie)
    - Genre (each)
    - Casts
    - Directors

Open issues / tasks
    - when adding person in add movie (there's an error) di ko pa alam reason, but in edit it's working
    - when renaming movie, change name then the image (get movieID, then call image handler)
    - add to favorites
    - fix user review display: for now star emoji; later should be icons (loop by 5)
        - determine which position is clicked (better if Tailwind component)
    - style modal

Fields (undecided)
    - average_rating (on movies for easy avg — fewer queries)
    - runtime
    - rated
        - G (General Audience) — Suitable for all ages. No offensive content. (Toy Story)
        - PG (Parental Guidance Suggested) — Some material may not be suitable for children. (Harry Potter and the Sorcerer’s Stone)
        - PG-13 (Parents Strongly Cautioned) — Some material may be inappropriate for children under 13 (violence, mild language). (Most Marvel movies — Avengers, Spider-Man)
        - R (Restricted) — Under 17 requires accompanying parent or adult. Strong language, violence, nudity. (Deadpool, Logan)
        - NC-17 (Adults Only) — No one 17 and under admitted. Explicit adult content. (Rare in mainstream; usually indie films.)