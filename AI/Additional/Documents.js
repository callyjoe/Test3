const apiKey = "AIzaSyC0rn7BdF5Y_8ewCnUz8uQaPvzHNs2MAFA";  // Google Custom Search API (for PDFs)
const searchEngineId = "60b9b569521184326"; // Custom Search Engine ID
const imageApiKey = "5s689RrsfWzLeiqjDH71Ldv8Y3ubPmkRpWCS3EqtvXCHPzvtleLA9ZRA"; // API Key for Image Search (Pexels or Unsplash)

let topic = sessionStorage.getItem("resourceTopic") || "Information Technology";

window.onload = () => {
  const input = document.getElementById("searchInput");
  input.value = topic;
  searchDocuments(topic);
};

function searchDocuments(queryTopic = null) {
  const input = document.getElementById("searchInput");
  const searchQuery = queryTopic || input.value || "academic pdf";

  const query = `${searchQuery} filetype:pdf`;

  // Fetch documents using Google Custom Search API
  fetch(`https://www.googleapis.com/customsearch/v1?q=${encodeURIComponent(query)}&key=${apiKey}&cx=${searchEngineId}`)
    .then(res => res.json())
    .then(data => {
      const results = data.items || [];
      const resultsDiv = document.getElementById("results");
      resultsDiv.innerHTML = "";

      if (results.length === 0) {
        resultsDiv.innerHTML = "<p>No results found.</p>";
        return;
      }

      results.forEach(item => {
        const card = document.createElement("div");
        card.className = "card";

        // Fetch a unique image related to the document's title or snippet
        const imageQuery = item.title || item.snippet || queryTopic; // Use title, snippet or topic for the image search

        fetch(`https://api.pexels.com/v1/search?query=${encodeURIComponent(imageQuery)}&per_page=1`, {
          headers: {
            "Authorization": imageApiKey
          }
        })
        .then(response => response.json())
        .then(imageData => {
          // Use image from Pexels API or fallback to a default image if not available
          const imageUrl = imageData.photos && imageData.photos[0] ? imageData.photos[0].src.medium : "https://via.placeholder.com/300x180?text=No+Image"; 

          card.innerHTML = `
            <img src="${imageUrl}" alt="${item.title}" />
            <h3>${item.title}</h3>
            <p>${item.snippet}</p>
            <a href="${item.link}" target="_blank">Download PDF</a>
          `;
          resultsDiv.appendChild(card);
        })
        .catch(err => {
          // In case of an error with Pexels API, use a fallback image
          const imageUrl = "https://via.placeholder.com/300x180?text=No+Image";
          card.innerHTML = `
            <img src="${imageUrl}" alt="${item.title}" />
            <h3>${item.title}</h3>
            <p>${item.snippet}</p>
            <a href="${item.link}" target="_blank">Download PDF</a>
          `;
          resultsDiv.appendChild(card);
        });
      });
    })
    .catch(err => {
      console.error("Error fetching results", err);
      document.getElementById("results").innerHTML = "<p>Failed to load documents.</p>";
    });
}
