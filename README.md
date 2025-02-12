# AI-Generated Blogpost with DALL·E

A WordPress plugin that automatically generates daily or weekly blog posts with DALL·E feutured image.

## Core Features

- 🤖 Automated blog post generation using OpenAI's GPT models
- 🎨 DALL·E image generation for featured images
- ⏱️ Customizable posting frequency (daily/weekly)
- 🎯 Dynamic model selection with API validation

## Technical Features

### API Integration
- OpenAI GPT-4/3.5 support
- DALL·E 3/2 image generation
- Dynamic model selection
- Robust error handling

## Content Generation

### System Role

```text
Write for a website a SEO blogpost with the [category] as keyword
```
### Content Template

```text
Write for a website a SEO blogpost with the [category] as keyword. Use sections:
||Title||:
||Content||:
||Category||:[category]
Write the content of the content section within the <article></article> tags and use <p>, <h1>, and <h2>.
```

### Image Settings
- Size options: 1024x1024, 1792x1024, 1024x1792
- Style: Vivid/Natural
- Quality: Standard/HD
- Custom prompt templates

## Configuration

### Basic Setup
1. Upload to `/wp-content/plugins/`
2. Activate plugin
3. Enter OpenAI API key
4. Configure posting schedule

### Advanced Settings
- Temperature control
- Token limits
- System role customization
- Custom prompt templates
- Category management

## Usage Examples

### DALL·E Prompt Template
```text
Design a visually engaging and modern header image specifically for a blog post on the theme of [category]. This image should serve as the focal point at the top of the blog post, drawing readers in immediately. Ensure the composition is balanced and detailed with high-quality visuals. Embrace a clean, professional style that incorporates elements directly related to the topic, symbolizing [category] effectively. The background should enhance the main subject without overwhelming it, while a harmonious, appealing color scheme is used throughout. A subtle depth of field effect should be applied to ensure the key elements stand out, making the image not only eye-catching but perfectly suited as a blog post header.
```

## Requirements

- WordPress 5.0+
- PHP 7.4+
- OpenAI API key
- DALL·E API access (optional)

## Support

Visit our [GitHub repository](https://github.com/vdecommerce/AI-Blogpost)

## License

Copyright (c) 2025 Van Dijken E-Commerce

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.