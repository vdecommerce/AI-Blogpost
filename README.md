# AI-Generated Blogpost with DALL·E

A WordPress plugin that automatically generates daily or weekly blog posts with DALL·E featured images in multiple languages.

## ✨ Core Features

- 🤖 Automated blog post generation using OpenAI's GPT models
- 🎨 DALL·E image generation for featured images
- 🌍 Multi-language support (EN, NL, DE, FR, ES)
- ⏱️ Customizable posting frequency (daily/weekly)
- 🎯 Dynamic model selection with API validation
- 💾 Smart caching system for optimal performance

## 🛠️ Technical Features

### API Integration
- OpenAI GPT-4/3.5 support
- DALL·E 3/2 image generation
- Dynamic model selection
- Robust error handling
- Language-specific content generation

## 📝 Content Generation

### Multi-Language Support
| Language | Code |
|----------|------|
| English  | en   |
| Dutch    | nl   |
| German   | de   |
| French   | fr   |
| Spanish  | es   |

### System Role Template
```text
Write for a website a SEO blogpost in [language] with the [category] as keyword
```

### Content Template
```text
Write for a website a SEO blogpost in [language] with the [category] as keyword. Use sections:
||Title||:
||Content||:
||Category||:[category]
Write the content of the content section within the <article></article> tags and use <p>, <h1>, and <h2>.
```

## 🎨 Image Settings
- **Sizes**: 1024x1024, 1792x1024, 1024x1792
- **Styles**: Vivid/Natural
- **Quality**: Standard/HD
- **Language**: Automatic prompt translation
- **Templates**: Customizable per language

## ⚙️ Configuration

### Quick Start
1. Upload to `/wp-content/plugins/ai-blogpost`
2. Activate plugin
3. Enter OpenAI API key
4. Select default language
5. Configure posting schedule

### Advanced Configuration
| Setting | Description |
|---------|-------------|
| Language | Select content language |
| Temperature | Control creativity (0.0-1.0) |
| Tokens | Set maximum length |
| System Role | Customize AI behavior |
| Categories | Manage post categories |
| Cache | Control data persistence |

## 💡 Usage Examples

### DALL·E Prompt Template
```text
Design a visually engaging and modern header image specifically for a blog post on the theme of [category] in [language] style. This image should serve as the focal point at the top of the blog post, drawing readers in immediately. Ensure the composition is balanced and detailed with high-quality visuals. Embrace a clean, professional style that incorporates elements directly related to the topic, symbolizing [category] effectively. The background should enhance the main subject without overwhelming it, while a harmonious, appealing color scheme is used throughout.
```

## 📋 Requirements

| Requirement | Version/Details |
|-------------|----------------|
| WordPress | 5.0+ |
| PHP | 7.4+ |
| OpenAI API | Valid key |
| DALL·E API | Optional |

## 🔄 Cache Management

- **Automatic**: Clears after settings updates
- **Manual**: Refresh via dashboard
- **Smart**: Optimized for performance
- **Persistent**: Survives plugin updates

## 🆘 Support

Need help? Visit our [GitHub repository](https://github.com/vdecommerce/AI-Blogpost) or open an issue.

## 📄 License

Copyright (c) 2025 Van Dijken E-Commerce

Licensed under the MIT License. See LICENSE file for full terms.