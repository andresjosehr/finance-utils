import { useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import AuthenticatedLayout from '@/layouts/authenticated-layout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Trash2, Edit, Eye, ToggleLeft, ToggleRight, Plus, Settings, BarChart3, CheckSquare } from 'lucide-react'
import { toast } from 'sonner'

interface TradingPair {
    id: number
    asset: string
    fiat: string
    pair_symbol: string
    is_active: boolean
    collection_interval_minutes: number
    collection_config: any
    min_trade_amount: number | null
    max_trade_amount: number | null
    volume_ranges: number[] | null
    use_volume_sampling: boolean
    default_sample_volume: number
    last_collection: string | null
    collection_status: any
}

interface Props {
    pairs: TradingPair[]
}

export default function Index({ pairs }: Props) {
    const [search, setSearch] = useState('')
    const [showBulkDialog, setShowBulkDialog] = useState(false)
    const [selectedPairs, setSelectedPairs] = useState<number[]>([])
    const [bulkAction, setBulkAction] = useState('')
    const [bulkVolumeRanges, setBulkVolumeRanges] = useState('')
    const [bulkDefaultVolume, setBulkDefaultVolume] = useState('')

    const filteredPairs = pairs.filter(pair =>
        pair.pair_symbol.toLowerCase().includes(search.toLowerCase()) ||
        pair.asset.toLowerCase().includes(search.toLowerCase()) ||
        pair.fiat.toLowerCase().includes(search.toLowerCase())
    )

    const handleToggleActive = (pair: TradingPair) => {
        router.post(route('admin.trading-pairs.toggle-active', pair.id), {}, {
            onSuccess: () => {
                toast.success(`${pair.pair_symbol} ${pair.is_active ? 'deactivated' : 'activated'} successfully`)
            },
            onError: () => {
                toast.error('Failed to update trading pair status')
            }
        })
    }

    const handleDelete = (pair: TradingPair) => {
        if (confirm(`Are you sure you want to delete ${pair.pair_symbol}?`)) {
            router.delete(route('admin.trading-pairs.destroy', pair.id), {
                onSuccess: () => {
                    toast.success('Trading pair deleted successfully')
                },
                onError: (errors) => {
                    const errorMessage = errors.delete || 'Failed to delete trading pair'
                    toast.error(errorMessage)
                }
            })
        }
    }

    const handleBulkAction = () => {
        if (selectedPairs.length === 0) {
            toast.error('Please select at least one trading pair')
            return
        }

        const data: any = {
            pair_ids: selectedPairs,
            action: bulkAction
        }

        if (bulkAction === 'enable' || bulkAction === 'update_ranges') {
            if (bulkVolumeRanges) {
                data.volume_ranges = bulkVolumeRanges.split(',').map(v => parseFloat(v.trim())).filter(v => !isNaN(v))
            }
        }

        if (bulkAction === 'update_volume') {
            if (bulkDefaultVolume) {
                data.default_sample_volume = parseFloat(bulkDefaultVolume)
            }
        }

        router.post(route('admin.trading-pairs.bulk-volume-sampling'), data, {
            onSuccess: () => {
                toast.success('Bulk update completed successfully')
                setShowBulkDialog(false)
                setSelectedPairs([])
                setBulkAction('')
                setBulkVolumeRanges('')
                setBulkDefaultVolume('')
            },
            onError: () => {
                toast.error('Failed to perform bulk update')
            }
        })
    }

    const togglePairSelection = (pairId: number) => {
        setSelectedPairs(prev =>
            prev.includes(pairId)
                ? prev.filter(id => id !== pairId)
                : [...prev, pairId]
        )
    }

    const selectAllPairs = () => {
        setSelectedPairs(
            selectedPairs.length === filteredPairs.length
                ? []
                : filteredPairs.map(pair => pair.id)
        )
    }

    const getStatusBadge = (pair: TradingPair) => {
        if (!pair.is_active) {
            return <Badge variant="secondary">Inactive</Badge>
        }
        
        const status = pair.collection_status
        const minutesSinceCollection = status.minutes_since_last_collection
        
        if (minutesSinceCollection === null) {
            return <Badge variant="outline">Never Collected</Badge>
        }
        
        if (minutesSinceCollection < 10) {
            return <Badge variant="default">Active</Badge>
        } else if (minutesSinceCollection < 30) {
            return <Badge variant="outline">Recent</Badge>
        } else {
            return <Badge variant="destructive">Stale</Badge>
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title="Trading Pairs Management" />
            
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Trading Pairs</h1>
                        <p className="text-muted-foreground">
                            Manage P2P trading pairs and their data collection configuration
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href={route('admin.trading-pairs.statistics')}>
                            <Button variant="outline" size="sm">
                                <BarChart3 className="h-4 w-4 mr-2" />
                                Statistics
                            </Button>
                        </Link>
                        <Link href={route('admin.trading-pairs.create')}>
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Trading Pair
                            </Button>
                        </Link>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div>
                                <CardTitle>Trading Pairs ({filteredPairs.length})</CardTitle>
                                <CardDescription>
                                    Configure assets, collection intervals, and volume sampling
                                </CardDescription>
                            </div>
                            <div className="flex items-center gap-2">
                                <Input
                                    placeholder="Search pairs..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-64"
                                />
                                {selectedPairs.length > 0 && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setShowBulkDialog(true)}
                                    >
                                        <Settings className="h-4 w-4 mr-2" />
                                        Bulk Actions ({selectedPairs.length})
                                    </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-12">
                                        <Checkbox
                                            checked={selectedPairs.length === filteredPairs.length && filteredPairs.length > 0}
                                            onCheckedChange={selectAllPairs}
                                        />
                                    </TableHead>
                                    <TableHead>Pair</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Interval</TableHead>
                                    <TableHead>Volume Sampling</TableHead>
                                    <TableHead>Last Collection</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredPairs.map((pair) => (
                                    <TableRow key={pair.id}>
                                        <TableCell>
                                            <Checkbox
                                                checked={selectedPairs.includes(pair.id)}
                                                onCheckedChange={() => togglePairSelection(pair.id)}
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col">
                                                <span className="font-medium">{pair.pair_symbol}</span>
                                                <span className="text-sm text-muted-foreground">
                                                    {pair.asset} → {pair.fiat}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {getStatusBadge(pair)}
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-sm">
                                                {pair.collection_interval_minutes}m
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-col">
                                                <Badge variant={pair.use_volume_sampling ? "default" : "secondary"} className="w-fit">
                                                    {pair.use_volume_sampling ? "Enabled" : "Disabled"}
                                                </Badge>
                                                {pair.use_volume_sampling && pair.volume_ranges && (
                                                    <span className="text-xs text-muted-foreground mt-1">
                                                        {pair.volume_ranges.join(', ')}
                                                    </span>
                                                )}
                                                {!pair.use_volume_sampling && (
                                                    <span className="text-xs text-muted-foreground mt-1">
                                                        Default: {pair.default_sample_volume}
                                                    </span>
                                                )}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-sm">
                                                {pair.last_collection
                                                    ? new Date(pair.last_collection).toLocaleString()
                                                    : 'Never'
                                                }
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Link href={route('admin.trading-pairs.show', pair.id)}>
                                                    <Button variant="ghost" size="sm">
                                                        <Eye className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Link href={route('admin.trading-pairs.edit', pair.id)}>
                                                    <Button variant="ghost" size="sm">
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                </Link>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleToggleActive(pair)}
                                                >
                                                    {pair.is_active ? (
                                                        <ToggleRight className="h-4 w-4 text-green-600" />
                                                    ) : (
                                                        <ToggleLeft className="h-4 w-4 text-gray-400" />
                                                    )}
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(pair)}
                                                    className="text-red-600 hover:text-red-800"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {filteredPairs.length === 0 && (
                            <div className="text-center py-8">
                                <p className="text-muted-foreground">
                                    {search ? 'No trading pairs match your search.' : 'No trading pairs found.'}
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Dialog open={showBulkDialog} onOpenChange={setShowBulkDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Bulk Volume Sampling Configuration</DialogTitle>
                        <DialogDescription>
                            Configure volume sampling for {selectedPairs.length} selected trading pairs.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium">Action</label>
                            <Select value={bulkAction} onValueChange={setBulkAction}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select action" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="enable">Enable Volume Sampling</SelectItem>
                                    <SelectItem value="disable">Disable Volume Sampling</SelectItem>
                                    <SelectItem value="update_ranges">Update Volume Ranges</SelectItem>
                                    <SelectItem value="update_volume">Update Default Volume</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {(bulkAction === 'enable' || bulkAction === 'update_ranges') && (
                            <div>
                                <label className="text-sm font-medium">Volume Ranges</label>
                                <Input
                                    placeholder="e.g., 100,500,1000,2500,5000"
                                    value={bulkVolumeRanges}
                                    onChange={(e) => setBulkVolumeRanges(e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground mt-1">
                                    Comma-separated values
                                </p>
                            </div>
                        )}

                        {bulkAction === 'update_volume' && (
                            <div>
                                <label className="text-sm font-medium">Default Sample Volume</label>
                                <Input
                                    type="number"
                                    placeholder="500"
                                    value={bulkDefaultVolume}
                                    onChange={(e) => setBulkDefaultVolume(e.target.value)}
                                />
                            </div>
                        )}
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowBulkDialog(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleBulkAction} disabled={!bulkAction}>
                            Apply Changes
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    )
}