#  Movie People Component (Directors & Casts)

## Overview
This feature allows users to **view, add, and remove** movie directors and cast members dynamically using **Livewire**.  
It is made up of two main parts:
1. The **Blade layout** that displays the sections for Directors and Casts.
2. The **Livewire component** that handles the actual logic for loading, adding, and removing people.

---

## Flow and Sequence

### 1. Displaying the Sections
In the Blade template, there are **two separate sections**:
- One for **Directors**
- One for **Casts (Actors & Actresses)**

Each section calls the same Livewire component (`movie-people`) but with a different role:
```blade
@livewire('movie-people', ['movie' => $movie, 'role' => 'Director'], key('director-'.$movie->id))
@livewire('movie-people', ['movie' => $movie, 'role' => 'Cast'], key('cast-'.$movie->id))
```

## 2. Inside the Livewire Component

### Mounting the Component
When the component starts, it receives:
- The **movie** (current movie object)
- The **role** (“Director” or “Cast”)

It then runs the `loadPeople()` function to get all the people linked to that movie with the matching role.

---

### Loading People
The `loadPeople()` method:
1. Checks if there’s a valid movie record.
2. Retrieves all people connected to that movie under the given role.
3. Sorts them alphabetically by name.
4. Stores them in the `$people` array for display.

This ensures the list is always accurate and automatically updates whenever the page or data changes.

---

### Adding a Person
When the user types a name in the input box and presses **Enter**:
1. The component reads the `searchName` input.
2. If the name already exists in the database, it reuses that record.
3. If not, it creates a new person record.
4. That person is then **attached** to the current movie with their respective role (either “Director” or “Cast”).
5. The component refreshes the list so the new person appears immediately.

---

### Removing a Person
When the user clicks the **“x”** icon beside a person’s name:
1. The component removes that person from the movie for that specific role.
2. The list reloads instantly to reflect the change.

---

## 3. Visual Layout and Behavior

Each person is displayed as a **badge** with:
- Their **name**
- A **role-based icon**
  - Director → `bx-user-voice`
  - Cast → `bx-user`


## 💡 Summary of User Actions

| Action | What Happens |
|--------|----------------|
| Typing a name and pressing Enter | Adds a new Director or Cast member |
| Clicking the "x" beside a name | Removes that person from the list |
| Reloading or opening the movie page | Automatically loads and displays all people for that role |

---

## 🧭 Overall Flow Summary

1. **Page loads** → The Blade view renders both sections: Directors and Casts.  
2. **Each section mounts its own Livewire component.**  
3. **`loadPeople()` fetches people** for the movie and role.  
4. **User can add or remove people dynamically.**  
5. **Livewire automatically re-renders the list** without reloading the page.
