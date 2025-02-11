# AI-Generated Tarot Blogpost with DALL·E

A WordPress plugin that automatically generates daily or weekly blog posts with tarot-like correspondences and DALL·E images.

## Features

- Automated blog post generation using OpenAI's GPT models
- DALL·E image generation for featured images
- Customizable posting frequency (daily/weekly)
- Rich correspondence system including:
  - Planets
  - Colors
  - Days
  - Celestial bodies
  - Hermetic principles
  - Minerals
  - Scents
  - Plants/herbs
  - Greek pantheon
- Custom category support
- Date shortcode support [datum]
- Configurable GPT and DALL·E settings

## Installation

1. Upload the plugin files to `/wp-content/plugins/tarot-blogpost`
2. Activate the plugin in WordPress admin
3. Go to "AI Blogpost" in admin menu
4. Configure your OpenAI API keys and settings

## Settings

### Text Generation
- OpenAI API Key
- GPT Model (e.g., gpt-4)
- Temperature
- Max tokens
- System role
- Custom prompt
- Post frequency
- Categories

### DALL·E Settings
- Enable/disable DALL·E
- DALL·E API Key
- Image size
- Style (vivid/natural)
- Quality (standard/HD)
- Model selection

## Usage

The plugin will automatically generate posts based on your frequency settings. You can also:

- Manually trigger a test post from the settings page
- View next scheduled post time
- Customize the correspondence system
- Configure post categories

## Prompt Examples

### DALL·E Image Prompt Template
```text
Create a hyper-realistic photo of a surreal landscape where all elements are harmoniously and naturally integrated.

The background showcases a majestic depiction of the planet [Planet], floating above the horizon and reflected in a still, crystal-clear lake.

In the foreground, a lifelike [Animal] is portrayed with breathtaking detail, featuring shimmering eyes, perfectly rendered fur or scales, and a powerful yet serene posture. The animal stands on a natural surface that symbolizes the element [Element], such as a rocky cliff, a softly glowing water source, or a golden sunlit sand dune.

The dominant color [Color] fills the scene with a warm or cool glow, for example, in the light of a sunrise or sunset, with the hues beautifully reflected in the landscape. Subtle references to the number [Number] are scattered throughout, such as [Number] blooming flowers, water droplets, or reflections in the lake, forming a hidden pattern.

A large [Crystal] is prominently placed in the scene, rendered with hyper-realistic details of shine, refraction patterns, and light reflections, as if it is the energy source of the entire environment. Across the landscape, lifelike plants of [Plant/Herb] grow, featuring intricate details such as leaf textures and delicate flowers, adding a sense of life and growth.

The entire composition radiates a perfect balance between realism and mysticism, with stunning details and an atmosphere of natural harmony. The scene should feel like a photograph of an alien yet believable world, where all elements are logically and realistically integrated.
```
### Prompt for Daily Blog Post

```text
||Title||:
"Generate a unique title that captures the essence of the day by incorporating philosophical, occult, and spiritual daily correspondences. Combine the following elements in the title:

Date: [date]
Planet: [Planet]
Hermetic Principle: [Principle]
Color: [Color]
Element: [Element]
For example: 'Saturday Under Saturn: The Principle of Polarity in Lead and Indigo'."

||Content||
Meaning and Symbolism
Provide an in-depth interpretation of the day based on its specific philosophical and occult associations. Explain the significance of each element and how they are interconnected. Use the following structure:

Planet: Describe the influence of [Planet] on the day.
Element: How does [Element] function as the energetic theme?
Color: What is the symbolism of [Color] today?
Animal: What is the meaning of [Animal] as a spiritual guide?
Number: What energetic significance does [Number] hold?
Crystal: How does [Crystal] support the energy of the day?
Plant/Herb: How can [Plant/Herb] be used for healing or magic?
Chakra: What role does [Chakra] play today in finding balance?
Tree: What does [Tree] symbolize today?
Tarot Card: How does [Tarot Card] align with today’s energy?
Season: How does the current [Season] influence the symbolism?
World: Describe the theme of cosmic or earthly energies.
Alchemy: What phase or transformation is represented by [Alchemy]?
Mythology: Provide insights from relevant pantheons:
Greek: [pantheon_greek]
Egyptian: [pantheon_egyptian]
Celtic: [pantheon_celtic]
Norse: [pantheon_norse]
Druidic: [pantheon_druidic]
Affirmation
Write a powerful affirmation that aligns with today’s symbolism. For example:

"Today, I embrace the power of [Planet] and work in harmony with the energy of [Element]. I am filled with [Color] and strengthened by [Crystal]."

Ritual
Offer a simple yet inspiring ritual to harness the day’s energy. For example:

Ritual: Light a candle in the color [Color]. Visualize the energy of [Planet] filling your aura as you hold [Crystal] in your hand and burn a sprig of [Plant/Herb]. Imagine yourself fully aligned with today’s power.

||Category||:
"Daily Inspiration, [category]"
```

### Example Values
- Planet: Mars
- Animal: Wolf
- Element: Fire
- Color: Deep Purple
- Number: Seven
- Crystal: Amethyst
- Plant/Herb: Lavender

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- OpenAI API key
- DALL·E API access (optional)

## License

Copyright (c) 2025 Van Dijken E-Commerce

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.