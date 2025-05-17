document.addEventListener("DOMContentLoaded", () => {
    const ratingContainer = document.querySelector(".rating-container");
    if (!ratingContainer) return;

    const bookId = ratingContainer.dataset.bookId;
    const ratingEl = document.getElementById("star-rating");
    const averageEl = document.querySelector(".rating-average");

    const currentRating = parseFloat(ratingEl.dataset.currentRating || 0);

    const maxStars = 5;
    const step = 0.5;

    const renderStars = (highlighted = null) => {
        ratingEl.innerHTML = "";
        for (let i = step; i <= maxStars; i += step) {
            const span = document.createElement("span");
            span.classList.add("star");
            if (i <= (highlighted ?? currentRating)) {
                span.classList.add("filled");
            }
            //parce que je veux des demi étoiles
            span.innerHTML = i % 1 === 0 ? "★" : "☆";
            span.dataset.value = i;
            ratingEl.appendChild(span);
        }
    };

    renderStars();

    ratingEl.addEventListener("mouseover", (e) => {
        if (e.target.classList.contains("star")) {
            renderStars(parseFloat(e.target.dataset.value));
        }
    });

    ratingEl.addEventListener("mouseout", () => {
        renderStars();
    });

    ratingEl.addEventListener("click", (e) => {
        if (!e.target.classList.contains("star")) return;
        const selectedRating = parseFloat(e.target.dataset.value);

        fetch(`/book/${bookId}/rate`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({ rating: selectedRating })
        })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                averageEl.textContent = `Moyenne : ${data.average}`;
                renderStars(selectedRating);
            } else {
                alert("Erreur lors de l'enregistrement de votre note.");
            }
        });
    });
});
