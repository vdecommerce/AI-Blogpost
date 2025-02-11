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