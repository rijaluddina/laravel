const swiper = new Swiper(".swiper", {
    // Optional parameters
    slidesOffsetAfter: 16,
    slidesOffsetBefore: 16,
    slidesPerView: "auto",
    spaceBetween: 12,
    centerInsufficientSlides: true,
});

const mainThumbnail = document.getElementById("main-thumbnail");
const imgSelectors = document.querySelectorAll(".thumbnail-selector");
imgSelectors.forEach((element) => {
    element.addEventListener("click", () => {
        const imgElement = element.querySelector("img");
        if (imgElement) {
            mainThumbnail.src = imgElement.src;
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const sizeRadios = document.querySelectorAll('input[name="shoe_size"]');
    const sizeIdInput = document.getElementById("size_id");

    sizeRadios.forEach(radio => {
        radio.addEventListener("change", function () {
            const selectedSizeId = this.getAttribute("data-size-id");
            sizeIdInput.value = selectedSizeId;
        });
    });
});
