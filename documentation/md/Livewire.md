# Livewire Overview

Livewire is a full-stack framework for Laravel that allows you to build dynamic interfaces without writing JavaScript. It connects your front-end Blade templates to backend PHP components seamlessly.

---

## How Livewire Works

1. **Blade & Livewire Components**
   - You create a Livewire component (PHP class + Blade view).  
   - The Blade view can have directives like `wire:click`, `wire:model`, `wire:submit.prevent`, etc.  

2. **Front-End Interaction**
   - When a user interacts with a `wire:*` directive (e.g., clicks a button), Livewire intercepts the event.  
   - Livewire sends an AJAX request to the Laravel server containing:
     - Component state
     - Action being called

3. **Server-Side Processing**
   - The corresponding method in the Livewire PHP component runs (e.g., `toggleFavorite()`, `submitReview()`).  
   - Any changes to public properties (`$isFavorited`, `$reviews`, `$rating`) are recorded.  

4. **HTML Diffing & Component Re-render**
   - Livewire re-renders the Blade view **on the server** using the updated component state.  
   - Only the **HTML that changed** is sent back to the browser (diff patch), not the whole page.  
   - The front-end swaps the old component HTML with the new one.

5. **Dynamic UI Updates**
   - User sees the updated state instantly, without page reload.  
   - State is preserved across actions unless explicitly reset.

---

## Key Points

- Livewire allows **reactive, dynamic components** with Laravel backend.  
- It avoids writing custom JavaScript for most UI interactions.  
- Works via **AJAX requests + HTML diffing**.  
- Events (`$dispatch` and `#[On]`) allow **cross-component communication**.  
- Public properties store the **component state** across requests.
