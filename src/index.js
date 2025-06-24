/**
 * DMG Read More Block
 */

import './editor.css';
import './style.css';

const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, TextControl, Button, Spinner } = wp.components;
const { useState, useEffect } = wp.element;
const apiFetch = wp.apiFetch;
const { addQueryArgs } = wp.url;
const { sprintf } = wp.i18n;

registerBlockType('dmg/read-more', {
    title: __('DMG Read More', 'dmg-read-more'),
    description: __('Insert a stylized link to another post', 'dmg-read-more'),
    category: 'widgets',
    icon: 'admin-links',
    attributes: {
        postId: {
            type: 'number',
            default: 0,
        },
        postTitle: {
            type: 'string',
            default: '',
        },
        postUrl: {
            type: 'string',
            default: '',
        },
    },
    supports: {
        html: false,
    },

    edit: function(props) {
        const { attributes, setAttributes } = props;
        const { postId, postTitle, postUrl } = attributes;

        const [searchTerm, setSearchTerm] = useState('');
        const [searchResults, setSearchResults] = useState([]);
        const [isLoading, setIsLoading] = useState(false);
        const [currentPage, setCurrentPage] = useState(1);
        const [totalPages, setTotalPages] = useState(1);

        // Load recent posts on mount
        useEffect(() => {
            fetchPosts('', 1);
        }, []);

        // Fetch posts from REST API
        const fetchPosts = async (search, page) => {
            setIsLoading(true);
            try {
                const queryArgs = {
                    search: search,
                    page: page,
                    per_page: 10,
                };

                const response = await apiFetch({
                    path: addQueryArgs('/dmg-read-more/v1/search-posts', queryArgs),
                });

                setSearchResults(response.posts);
                setTotalPages(response.pages);
                setCurrentPage(page);
            } catch (error) {
                console.error('Error fetching posts:', error);
                setSearchResults([]);
            } finally {
                setIsLoading(false);
            }
        };

        // Handle search
        const handleSearch = () => {
            setCurrentPage(1);
            fetchPosts(searchTerm, 1);
        };

        // Handle search input change
        const handleSearchChange = (value) => {
            setSearchTerm(value);
            // Reset to first page when search term changes
            setCurrentPage(1);
        };

        // Handle post selection
        const selectPost = (post) => {
            setAttributes({
                postId: post.id,
                postTitle: post.title,
                postUrl: post.url,
            });
        };

        // Handle pagination
        const handlePrevPage = () => {
            if (currentPage > 1) {
                fetchPosts(searchTerm, currentPage - 1);
            }
        };

        const handleNextPage = () => {
            if (currentPage < totalPages) {
                fetchPosts(searchTerm, currentPage + 1);
            }
        };

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Post Selection', 'dmg-read-more')}>
                        <div className="dmg-read-more-search">
                            <TextControl
                                label={__('Search Posts', 'dmg-read-more')}
                                value={searchTerm}
                                onChange={handleSearchChange}
                                placeholder={__('Enter search term or post ID', 'dmg-read-more')}
                                onKeyPress={(e) => {
                                    if (e.key === 'Enter') {
                                        handleSearch();
                                    }
                                }}
                            />
                            <Button
                                isPrimary
                                onClick={handleSearch}
                                disabled={isLoading}
                            >
                                {__('Search', 'dmg-read-more')}
                            </Button>
                        </div>

                        {isLoading && (
                            <div className="dmg-read-more-loading">
                                <Spinner />
                            </div>
                        )}

                        {!isLoading && searchResults.length > 0 && (
                            <>
                                <div className="dmg-read-more-results">
                                    <h4>{__('Select a Post:', 'dmg-read-more')}</h4>
                                    {searchResults.map((post) => (
                                        <Button
                                            key={post.id}
                                            isSecondary
                                            onClick={() => selectPost(post)}
                                            className={`dmg-read-more-result ${post.id === postId ? 'selected' : ''}`}
                                        >
                                            {post.title}
                                        </Button>
                                    ))}
                                </div>

                                {totalPages > 1 && (
                                    <div className="dmg-read-more-pagination">
                                        <Button
                                            isSmall
                                            onClick={handlePrevPage}
                                            disabled={currentPage === 1}
                                        >
                                            {__('Previous', 'dmg-read-more')}
                                        </Button>
                                        <span>
                                            {sprintf(__('Page %d of %d', 'dmg-read-more'), currentPage, totalPages)}
                                        </span>
                                        <Button
                                            isSmall
                                            onClick={handleNextPage}
                                            disabled={currentPage === totalPages}
                                        >
                                            {__('Next', 'dmg-read-more')}
                                        </Button>
                                    </div>
                                )}
                            </>
                        )}

                        {!isLoading && searchResults.length === 0 && searchTerm && (
                            <p>{__('No posts found.', 'dmg-read-more')}</p>
                        )}

                        {postId > 0 && (
                            <div className="dmg-read-more-selected">
                                <h4>{__('Currently Selected:', 'dmg-read-more')}</h4>
                                <p><strong>{postTitle}</strong></p>
                            </div>
                        )}
                    </PanelBody>
                </InspectorControls>

                <div className="dmg-read-more-editor">
                    {postId > 0 && postUrl ? (
                        <p className="dmg-read-more">
                            Read More: <a href={postUrl}>{postTitle}</a>
                        </p>
                    ) : (
                        <p className="dmg-read-more-placeholder">
                            {__('Select a post from the inspector controls →', 'dmg-read-more')}
                        </p>
                    )}
                </div>
            </>
        );
    },

    save: function() {
        // Rendered server-side
        return null;
    },
});